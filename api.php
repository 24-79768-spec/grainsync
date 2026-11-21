<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

try {
    switch ($action) {
        case 'bootstrap':
            $categories = $pdo->query('SELECT c.id,c.name,c.description FROM categories c WHERE EXISTS (SELECT 1 FROM menu_items m JOIN menu_recipes r ON r.menu_item_id=m.id WHERE m.category_id=c.id AND m.is_available=1) ORDER BY c.sort_order,c.name')->fetchAll();
            $menu = $pdo->query('SELECT m.id,m.category_id,m.name,m.description,m.price,m.image_url,m.is_featured,c.name category_name FROM menu_items m JOIN categories c ON c.id=m.category_id WHERE m.is_available=1 AND EXISTS (SELECT 1 FROM menu_recipes r WHERE r.menu_item_id=m.id) ORDER BY m.is_featured DESC,m.id')->fetchAll();
            json_response(['categories' => $categories, 'menu' => $menu, 'user' => current_user(), 'csrf' => $_SESSION['csrf_token'], 'shop' => ['lat' => SHOP_LAT, 'lng' => SHOP_LNG, 'rate' => DELIVERY_RATE_PER_KM]]);

        case 'login':
            require_csrf();
            $email = filter_var(trim((string)($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string)($input['password'] ?? '');
            if (!$email || $password === '') {
                json_response(['error' => 'Enter a valid email and password.'], 422);
            }
            $stmt = $pdo->prepare('SELECT id,name,email,password_hash,role,phone FROM users WHERE email=? AND is_active=1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                json_response(['error' => 'Email or password is incorrect.'], 422);
            }
            session_regenerate_id(true);
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            log_activity($pdo, (int)$user['id'], 'login', 'Signed in');
            json_response(['user' => $user, 'csrf' => $_SESSION['csrf_token']]);

        case 'register':
            require_csrf();
            $name = clean_text($input['name'] ?? '', 120);
            $email = filter_var(trim((string)($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $phone = clean_text($input['phone'] ?? '', 30);
            $password = (string)($input['password'] ?? '');
            if ($name === '' || !$email || strlen($password) < 8) {
                json_response(['error' => 'Name and valid email are required; password must be at least 8 characters.'], 422);
            }
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,phone) VALUES (?,?,?,'customer',?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null]);
            $user = ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'customer', 'phone' => $phone];
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            log_activity($pdo, $user['id'], 'register', 'Customer account created');
            json_response(['user' => $user, 'csrf' => $_SESSION['csrf_token']], 201);

        case 'logout':
            require_csrf();
            $user = current_user();
            if ($user) {
                log_activity($pdo, (int)$user['id'], 'logout', 'Signed out');
            }
            $_SESSION = [];
            session_destroy();
            json_response(['ok' => true]);

        case 'order_status':
            $publicId = strtoupper(clean_text($_GET['id'] ?? $input['id'] ?? '', 24));
            $stmt = $pdo->prepare('SELECT public_id,status,order_type,created_at,updated_at FROM orders WHERE public_id=?');
            $stmt->execute([$publicId]);
            $order = $stmt->fetch();
            json_response(['order' => $order ?: null]);

        case 'chat':
            $message = mb_strtolower(clean_text($input['message'] ?? '', 500));
            if (preg_match('/RM-[A-Z0-9-]+/', strtoupper($message), $match)) {
                $stmt = $pdo->prepare('SELECT public_id,status,order_type FROM orders WHERE public_id=?');
                $stmt->execute([$match[0]]);
                $order = $stmt->fetch();
                $reply = $order ? "Order {$order['public_id']} is currently {$order['status']}." : "I couldn't find that order ID. Please check it and try again.";
            } elseif (preg_match('/hour|open|close|time/', $message)) {
                $reply = 'We are open daily from 10:00 AM to 10:00 PM.';
            } elseif (preg_match('/menu|popular|best|food|meal/', $message)) {
                $items = $pdo->query('SELECT name,price FROM menu_items WHERE is_available=1 ORDER BY is_featured DESC LIMIT 3')->fetchAll();
                $reply = 'Customer favorites: ' . implode(', ', array_map(fn($item) => $item['name'] . ' (₱' . number_format((float)$item['price'], 2) . ')', $items)) . '.';
            } elseif (preg_match('/deliver|fee|distance/', $message)) {
                $reply = 'Delivery is available in our service area. The fee is calculated from the shop location at ₱' . number_format(DELIVERY_RATE_PER_KM, 2) . ' per kilometer.';
            } else {
                $reply = 'I can help with store hours, menu favorites, delivery fees, or an order status. Ask away!';
            }
            json_response(['reply' => $reply]);

        case 'create_order':
        case 'manual_order':
            $user = $action === 'manual_order' ? require_user(['owner','staff']) : require_user(['customer']);
            require_csrf();
            $items = $input['items'] ?? [];
            if (!is_array($items) || count($items) < 1 || count($items) > 40) {
                json_response(['error' => 'Add at least one menu item.'], 422);
            }
            $type = $input['order_type'] ?? '';
            $payment = $input['payment_method'] ?? 'cash';
            if (!in_array($type, ['delivery','takeout'], true) || !in_array($payment, ['cash','online'], true)) {
                json_response(['error' => 'Choose a valid order type and payment option.'], 422);
            }
            $customerName = clean_text($input['customer_name'] ?? $user['name'], 120);
            $phone = clean_text($input['customer_phone'] ?? ($user['phone'] ?? ''), 30);
            $address = clean_text($input['delivery_address'] ?? '', 500);
            if ($customerName === '' || ($type === 'delivery' && $address === '')) {
                json_response(['error' => 'Customer name and a delivery address are required.'], 422);
            }
            $lat = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : null;
            $lng = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : null;
            if ($lat !== null && ($lat < -90 || $lat > 90) || $lng !== null && ($lng < -180 || $lng > 180)) {
                json_response(['error' => 'Invalid map coordinates.'], 422);
            }
            $distance = 0.0;
            if ($type === 'delivery' && $lat !== null && $lng !== null) {
                $earth = 6371;
                $dLat = deg2rad($lat - SHOP_LAT);
                $dLng = deg2rad($lng - SHOP_LNG);
                $a = sin($dLat / 2) ** 2 + cos(deg2rad(SHOP_LAT)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
                $distance = 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
            } elseif ($type === 'delivery' && $action === 'manual_order') {
                $distance = filter_var($input['distance_km'] ?? null,FILTER_VALIDATE_FLOAT);
                if ($distance === false || $distance <= 0 || $distance > 50) {
                    json_response(['error' => 'Enter an estimated delivery distance between 0 and 50 km.'], 422);
                }
            } elseif ($type === 'delivery') {
                json_response(['error' => 'Pin the delivery location on the map or geocode the address before placing the order.'], 422);
            }
            if ($distance > 50) {
                json_response(['error' => 'That destination is outside our 50 km delivery area.'], 422);
            }
            $fee = $type === 'delivery' ? ceil($distance * DELIVERY_RATE_PER_KM) : 0;
            $pdo->beginTransaction();
            $normalized = [];
            $subtotal = 0.0;
            foreach ($items as $line) {
                $menuId = filter_var($line['menu_item_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);
                if (!$menuId || !$quantity || $quantity < 1 || $quantity > 50) {
                    throw new InvalidArgumentException('Invalid item or quantity in the cart.');
                }
                $stmt = $pdo->prepare('SELECT id,name,price FROM menu_items WHERE id=? AND is_available=1 AND EXISTS (SELECT 1 FROM menu_recipes r WHERE r.menu_item_id=menu_items.id)');
                $stmt->execute([$menuId]);
                $menu = $stmt->fetch();
                if (!$menu) {
                    throw new InvalidArgumentException('A selected menu item is no longer available.');
                }
                $options = $line['options'] ?? [];
                if (!is_array($options)) {
                    $options = [];
                }
                $subtotal += (float)$menu['price'] * $quantity;
                $normalized[] = [$menu, $quantity, $options];
            }
            $publicId = 'RM-' . strtoupper(bin2hex(random_bytes(10)));
            $paidStatus = $payment === 'online' ? 'sandbox_paid' : 'unpaid';
            $customerId = $action === 'manual_order' ? null : (int)$user['id'];
            $creatorId = (int)$user['id'];
            $status = 'Pending';
            $stmt = $pdo->prepare('INSERT INTO orders (public_id,customer_id,created_by,customer_name,customer_phone,order_type,status,payment_method,payment_status,delivery_address,latitude,longitude,distance_km,delivery_fee,subtotal,total,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$publicId,$customerId,$creatorId,$customerName,$phone ?: null,$type,$status,$payment,$paidStatus,$type === 'delivery' ? $address : null,$lat,$lng,$distance,$fee,$subtotal,$subtotal+$fee,clean_text($input['notes'] ?? '',1000)]);
            $orderId = (int)$pdo->lastInsertId();
            $insertLine = $pdo->prepare('INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price,quantity,options_json) VALUES (?,?,?,?,?,?)');
            foreach ($normalized as [$menu, $quantity, $options]) {
                $insertLine->execute([$orderId,$menu['id'],$menu['name'],$menu['price'],$quantity,json_encode($options)]);
                $recipe = $pdo->prepare('SELECT ingredient_id,quantity_required FROM menu_recipes WHERE menu_item_id=?');
                $recipe->execute([$menu['id']]);
                foreach ($recipe->fetchAll() as $ingredientUse) {
                    $deduct = (float)$ingredientUse['quantity_required'] * $quantity;
                    $lock = $pdo->prepare('SELECT stock_quantity,name,low_stock_threshold FROM ingredients WHERE id=? FOR UPDATE');
                    $lock->execute([$ingredientUse['ingredient_id']]);
                    $ingredient = $lock->fetch();
                    if (!$ingredient || (float)$ingredient['stock_quantity'] < $deduct) {
                        throw new RuntimeException('Not enough stock for ' . ($ingredient['name'] ?? 'an ingredient') . '. Please contact the store.');
                    }
                    $pdo->prepare('UPDATE ingredients SET stock_quantity=stock_quantity-? WHERE id=?')->execute([$deduct,$ingredientUse['ingredient_id']]);
                    $pdo->prepare('INSERT INTO stock_movements (ingredient_id,user_id,order_id,quantity_change,reason) VALUES (?,?,?,?,?)')->execute([$ingredientUse['ingredient_id'],$creatorId,$orderId,-$deduct,'Order ' . $publicId]);
                    if ((float)$ingredient['stock_quantity'] - $deduct <= (float)$ingredient['low_stock_threshold']) {
                        log_activity($pdo,$creatorId,'low_stock_alert',$ingredient['name'] . ' stock reached threshold after ' . $publicId);
                    }
                }
            }
            log_activity($pdo,$creatorId,$action === 'manual_order' ? 'manual_order_created' : 'order_created','Order ' . $publicId);
            $pdo->commit();
            json_response(['order_id'=>$publicId,'status'=>$status,'total'=>round($subtotal+$fee,2),'delivery_fee'=>$fee,'distance_km'=>round($distance,2)],201);

        case 'my_orders':
            $user = require_user();
            if ($user['role'] === 'customer') {
                $stmt = $pdo->prepare('SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id=o.id) item_count FROM orders o WHERE o.customer_id=? ORDER BY o.created_at DESC LIMIT 100');
                $stmt->execute([$user['id']]);
            } elseif ($user['role'] === 'rider') {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE rider_id=? AND order_type="delivery" ORDER BY created_at DESC LIMIT 100');
                $stmt->execute([$user['id']]);
            } else {
                $stmt = $pdo->query('SELECT o.*,u.name rider_name FROM orders o LEFT JOIN users u ON u.id=o.rider_id ORDER BY o.created_at DESC LIMIT 150');
            }
            $orders = $stmt->fetchAll();
            foreach ($orders as &$order) {
                $line = $pdo->prepare('SELECT item_name,quantity,unit_price,options_json FROM order_items WHERE order_id=?');
                $line->execute([$order['id']]);
                $order['items'] = $line->fetchAll();
            }
            json_response(['orders'=>$orders,'shop'=>['lat'=>SHOP_LAT,'lng'=>SHOP_LNG,'rate'=>DELIVERY_RATE_PER_KM]]);

        case 'update_status':
            $user = require_user(['owner','staff','rider']);
            require_csrf();
            $id = clean_text($input['order_id'] ?? '',24);
            $status = $input['status'] ?? '';
            $allowed = $user['role'] === 'rider' ? ['Out for Delivery','Completed'] : ['Preparing','Ready','Out for Delivery','Completed','Cancelled'];
            if (!in_array($status,$allowed,true)) {
                json_response(['error'=>'Invalid status transition.'],422);
            }
            $stmt = $pdo->prepare('SELECT id,status,rider_id,order_type FROM orders WHERE public_id=?');
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!$order || ($user['role'] === 'rider' && (int)$order['rider_id'] !== (int)$user['id'])) {
                json_response(['error'=>'Order not found or not assigned to you.'],404);
            }
            $transitions = ['Pending'=>['Preparing','Cancelled'],'Preparing'=>['Ready','Cancelled'],'Ready'=>['Out for Delivery','Completed','Cancelled'],'Out for Delivery'=>['Completed'],'Completed'=>[],'Cancelled'=>[]];
            if (!in_array($status,$transitions[$order['status']],true)) {
                json_response(['error'=>'This order cannot move from ' . $order['status'] . ' to ' . $status . '.'],422);
            }
            if ($status === 'Out for Delivery' && $order['order_type'] !== 'delivery') {
                json_response(['error'=>'Takeout orders cannot be marked out for delivery.'],422);
            }
            $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status,$order['id']]);
            log_activity($pdo,(int)$user['id'],'order_status_updated',$id . ' → ' . $status);
            json_response(['ok'=>true]);

        case 'riders':
            require_user(['owner','staff']);
            $riders = $pdo->query("SELECT id,name,phone FROM users WHERE role='rider' AND is_active=1 ORDER BY name")->fetchAll();
            json_response(['riders'=>$riders]);

        case 'assign_rider':
            $user = require_user(['owner','staff']);
            require_csrf();
            $orderId = clean_text($input['order_id'] ?? '',24);
            $riderId = filter_var($input['rider_id'] ?? null,FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='rider' AND is_active=1");
            $stmt->execute([$riderId]);
            if (!$riderId || !$stmt->fetch()) {
                json_response(['error'=>'Choose an active rider.'],422);
            }
            $stmt = $pdo->prepare("UPDATE orders SET rider_id=? WHERE public_id=? AND order_type='delivery' AND status IN ('Ready','Out for Delivery')");
            $stmt->execute([$riderId,$orderId]);
            if (!$stmt->rowCount()) {
                json_response(['error'=>'Only ready delivery orders can be assigned.'],422);
            }
            log_activity($pdo,(int)$user['id'],'rider_assigned',$orderId . ' assigned to rider #' . $riderId);
            json_response(['ok'=>true]);

        case 'inventory':
            require_user(['owner','staff']);
            $ingredients = $pdo->query('SELECT id,name,unit,stock_quantity,low_stock_threshold FROM ingredients ORDER BY name')->fetchAll();
            json_response(['ingredients'=>$ingredients]);

        case 'restock':
            $user = require_user(['owner']);
            require_csrf();
            $ingredientId = filter_var($input['ingredient_id'] ?? null,FILTER_VALIDATE_INT);
            $quantity = filter_var($input['quantity'] ?? null,FILTER_VALIDATE_FLOAT);
            if (!$ingredientId || !$quantity || $quantity <= 0 || $quantity > 100000) {
                json_response(['error'=>'Enter a valid positive stock amount.'],422);
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE ingredients SET stock_quantity=stock_quantity+? WHERE id=?');
            $stmt->execute([$quantity,$ingredientId]);
            if (!$stmt->rowCount()) {
                $pdo->rollBack();
                json_response(['error'=>'Ingredient not found.'],404);
            }
            $pdo->prepare('INSERT INTO stock_movements (ingredient_id,user_id,quantity_change,reason) VALUES (?,?,?,?)')->execute([$ingredientId,$user['id'],$quantity,clean_text($input['reason'] ?? 'Manual restock',255)]);
            log_activity($pdo,(int)$user['id'],'stock_adjusted','Ingredient #' . $ingredientId . ' +' . $quantity);
            $pdo->commit();
            json_response(['ok'=>true]);

        case 'management_data':
            require_user(['owner']);
            $allMenu = $pdo->query('SELECT m.id,m.category_id,m.name,m.description,m.price,m.image_url,m.is_available,m.is_featured,c.name category_name FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY m.id DESC')->fetchAll();
            $allCategories = $pdo->query('SELECT id,name,description,sort_order FROM categories ORDER BY sort_order,name')->fetchAll();
            $allIngredients = $pdo->query('SELECT id,name,unit FROM ingredients ORDER BY name')->fetchAll();
            $users = $pdo->query('SELECT id,name,email,role,phone,is_active,created_at FROM users ORDER BY id')->fetchAll();
            $recipes = $pdo->query('SELECT menu_item_id,ingredient_id,quantity_required FROM menu_recipes ORDER BY menu_item_id,ingredient_id')->fetchAll();
            json_response(['menu'=>$allMenu,'categories'=>$allCategories,'ingredients'=>$allIngredients,'users'=>$users,'recipes'=>$recipes]);

        case 'menu_save':
            $user = require_user(['owner']);
            require_csrf();
            $id = filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            $categoryId = filter_var($input['category_id'] ?? null,FILTER_VALIDATE_INT);
            $name = clean_text($input['name'] ?? '',140);
            $description = clean_text($input['description'] ?? '',2000);
            $price = filter_var($input['price'] ?? null,FILTER_VALIDATE_FLOAT);
            $image = clean_text($input['image_url'] ?? '',500);
            if (!$categoryId || $name === '' || $price === false || $price < 0 || $price > 100000) {
                json_response(['error'=>'Enter a name, category, and valid price.'],422);
            }
            $categoryCheck=$pdo->prepare('SELECT id FROM categories WHERE id=?');
            $categoryCheck->execute([$categoryId]);
            if(!$categoryCheck->fetch()) json_response(['error'=>'Choose an existing category.'],422);
            if ($id) {
                $stmt=$pdo->prepare('UPDATE menu_items SET category_id=?,name=?,description=?,price=?,image_url=?,is_available=?,is_featured=? WHERE id=?');
                $stmt->execute([$categoryId,$name,$description ?: null,$price,$image ?: null,!empty($input['is_available'])?1:0,!empty($input['is_featured'])?1:0,$id]);
                if (!$stmt->rowCount()) {
                    $check=$pdo->prepare('SELECT id FROM menu_items WHERE id=?');
                    $check->execute([$id]);
                    if (!$check->fetch()) json_response(['error'=>'Menu item not found.'],404);
                }
                log_activity($pdo,(int)$user['id'],'menu_updated',$name);
            } else {
                $stmt=$pdo->prepare('INSERT INTO menu_items (category_id,name,description,price,image_url,is_available,is_featured) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$categoryId,$name,$description ?: null,$price,$image ?: null,!empty($input['is_available'])?1:0,!empty($input['is_featured'])?1:0]);
                log_activity($pdo,(int)$user['id'],'menu_created',$name);
            }
            json_response(['ok'=>true]);

        case 'menu_delete':
            $user=require_user(['owner']);
            require_csrf();
            $id=filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            $stmt=$pdo->prepare('UPDATE menu_items SET is_available=0 WHERE id=?');
            $stmt->execute([$id]);
            if (!$stmt->rowCount()) json_response(['error'=>'Menu item not found or already unavailable.'],404);
            log_activity($pdo,(int)$user['id'],'menu_archived','Menu item #' . $id);
            json_response(['ok'=>true]);

        case 'category_save':
            $user=require_user(['owner']);
            require_csrf();
            $id=filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            $name=clean_text($input['name'] ?? '',100);
            $description=clean_text($input['description'] ?? '',255);
            if($name==='') json_response(['error'=>'Category name is required.'],422);
            if($id) {
                $exists=$pdo->prepare('SELECT id FROM categories WHERE id=?');
                $exists->execute([$id]);
                if(!$exists->fetch()) json_response(['error'=>'Category not found.'],404);
                $stmt=$pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?');
                $stmt->execute([$name,$description ?: null,$id]);
            } else {
                $pdo->prepare('INSERT INTO categories (name,description) VALUES (?,?)')->execute([$name,$description ?: null]);
            }
            log_activity($pdo,(int)$user['id'],'category_saved',$name);
            json_response(['ok'=>true]);

        case 'category_delete':
            $user=require_user(['owner']);
            require_csrf();
            $id=filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            $stmt=$pdo->prepare('DELETE FROM categories WHERE id=? AND NOT EXISTS (SELECT 1 FROM menu_items WHERE category_id=?)');
            $stmt->execute([$id,$id]);
            if (!$stmt->rowCount()) json_response(['error'=>'Category not found or still contains menu items.'],422);
            log_activity($pdo,(int)$user['id'],'category_deleted','Category #' . $id);
            json_response(['ok'=>true]);

        case 'recipe_save':
            $user=require_user(['owner']);
            require_csrf();
            $menuId=filter_var($input['menu_item_id'] ?? null,FILTER_VALIDATE_INT);
            $recipe=$input['recipe'] ?? [];
            if(!$menuId || !is_array($recipe) || count($recipe)>100) json_response(['error'=>'Choose a menu item and provide a valid recipe.'],422);
            $pdo->beginTransaction();
            $check=$pdo->prepare('SELECT id FROM menu_items WHERE id=?');
            $check->execute([$menuId]);
            if(!$check->fetch()) {
                $pdo->rollBack();
                json_response(['error'=>'Menu item not found.'],404);
            }
            $pdo->prepare('DELETE FROM menu_recipes WHERE menu_item_id=?')->execute([$menuId]);
            $insert=$pdo->prepare('INSERT INTO menu_recipes (menu_item_id,ingredient_id,quantity_required) VALUES (?,?,?)');
            foreach($recipe as $entry) {
                $ingredientId=filter_var($entry['ingredient_id'] ?? null,FILTER_VALIDATE_INT);
                $quantity=filter_var($entry['quantity_required'] ?? null,FILTER_VALIDATE_FLOAT);
                if(!$ingredientId || $quantity===false || $quantity<=0 || $quantity>100000) throw new InvalidArgumentException('Recipe amounts must be positive values.');
                $insert->execute([$menuId,$ingredientId,$quantity]);
            }
            log_activity($pdo,(int)$user['id'],'recipe_saved','Recipe updated for menu item #' . $menuId);
            $pdo->commit();
            json_response(['ok'=>true]);

        case 'user_save':
            $user=require_user(['owner']);
            require_csrf();
            $id=filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            $name=clean_text($input['name'] ?? '',120);
            $email=filter_var(trim((string)($input['email'] ?? '')),FILTER_VALIDATE_EMAIL);
            $phone=clean_text($input['phone'] ?? '',30);
            $role=$input['role'] ?? '';
            $password=(string)($input['password'] ?? '');
            $active=array_key_exists('is_active',$input)?(!empty($input['is_active'])?1:0):null;
            if($name==='' || !$email || !in_array($role,['customer','owner','staff','rider'],true)) json_response(['error'=>'Name, valid email, and role are required.'],422);
            if($id && $id===(int)$user['id'] && ($role!=='owner' || $active===0)) json_response(['error'=>'You cannot disable or demote your own active owner account.'],422);
            if($id) {
                $exists=$pdo->prepare('SELECT id FROM users WHERE id=?');
                $exists->execute([$id]);
                if(!$exists->fetch()) json_response(['error'=>'Account not found.'],404);
                if($password!=='') {
                    if(strlen($password)<8) json_response(['error'=>'Password must have at least 8 characters.'],422);
                    if($active===null) {
                        $stmt=$pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=?,password_hash=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone ?: null,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
                    } else {
                        $stmt=$pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=?,is_active=?,password_hash=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone ?: null,$role,$active,password_hash($password,PASSWORD_DEFAULT),$id]);
                    }
                } else {
                    if($active===null) {
                        $stmt=$pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone ?: null,$role,$id]);
                    } else {
                        $stmt=$pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=?,is_active=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone ?: null,$role,$active,$id]);
                    }
                }
                log_activity($pdo,(int)$user['id'],'user_updated','Account #' . $id . ' updated');
            } else {
                if(strlen($password)<8) json_response(['error'=>'New account password must have at least 8 characters.'],422);
                $stmt=$pdo->prepare('INSERT INTO users (name,email,password_hash,role,phone,is_active) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$phone ?: null,$active??1]);
                log_activity($pdo,(int)$user['id'],'user_created',$email . ' (' . $role . ')');
            }
            json_response(['ok'=>true]);

        case 'user_delete':
            $user=require_user(['owner']);
            require_csrf();
            $id=filter_var($input['id'] ?? null,FILTER_VALIDATE_INT);
            if(!$id || $id===(int)$user['id']) json_response(['error'=>'You cannot delete your own account.'],422);
            $stmt=$pdo->prepare('UPDATE users SET is_active=0 WHERE id=?');
            $stmt->execute([$id]);
            if(!$stmt->rowCount()) json_response(['error'=>'Account not found or already inactive.'],404);
            log_activity($pdo,(int)$user['id'],'user_deactivated','Account #' . $id);
            json_response(['ok'=>true]);

        case 'analytics':
            require_user(['owner']);
            $sales = $pdo->query("SELECT 
                COALESCE(SUM(CASE WHEN created_at >= CURDATE() AND status <> 'Cancelled' THEN total END),0) daily,
                COALESCE(SUM(CASE WHEN created_at >= CURDATE()-INTERVAL 6 DAY AND status <> 'Cancelled' THEN total END),0) weekly,
                COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01') AND status <> 'Cancelled' THEN total END),0) monthly
                FROM orders")->fetch();
            $top = $pdo->query("SELECT item_name,SUM(quantity) sold FROM order_items i JOIN orders o ON o.id=i.order_id WHERE o.status<>'Cancelled' GROUP BY item_name ORDER BY sold DESC LIMIT 5")->fetchAll();
            $dailyCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status<>'Cancelled' AND created_at>=CURDATE()-INTERVAL 13 DAY")->fetchColumn();
            $average = $dailyCount / 14;
            $ingredientRows = $pdo->query("SELECT i.id,i.name,i.unit,COALESCE(SUM(CASE WHEN o.id IS NOT NULL THEN oi.quantity*mr.quantity_required ELSE 0 END),0) used
                FROM ingredients i LEFT JOIN menu_recipes mr ON mr.ingredient_id=i.id
                LEFT JOIN order_items oi ON oi.menu_item_id=mr.menu_item_id
                LEFT JOIN orders o ON o.id=oi.order_id AND o.status<>'Cancelled' AND o.created_at>=CURDATE()-INTERVAL 13 DAY
                GROUP BY i.id,i.name,i.unit ORDER BY used DESC LIMIT 8")->fetchAll();
            $ingredientNeeds = array_map(fn($row) => ['name'=>$row['name'],'unit'=>$row['unit'],'next_7_days'=>round((float)$row['used']/14*7,3)],$ingredientRows);
            $logs = $pdo->query('SELECT l.action,l.details,l.created_at,u.name user_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 50')->fetchAll();
            $ingredients = $pdo->query('SELECT id,name,unit,stock_quantity,low_stock_threshold FROM ingredients ORDER BY name')->fetchAll();
            json_response(['sales'=>$sales,'top'=>$top,'forecast'=>['daily_orders_average'=>round($average,1),'next_7_day_orders'=>round($average*7),'ingredient_needs'=>$ingredientNeeds],'ingredients'=>$ingredients,'logs'=>$logs]);

        default:
            json_response(['error'=>'Unknown API action.'],404);
    }
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error'=>$exception->getMessage()],422);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Rice Moto database operation: ' . $exception->getMessage());
    if ((string)$exception->getCode() === '23000') {
        json_response(['error'=>'That email, category name, or related record already exists.'],422);
    }
    json_response(['error'=>'The database request could not be completed. Please try again.'],500);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Rice Moto API: ' . $exception->getMessage());
    json_response(['error'=>$exception instanceof RuntimeException ? $exception->getMessage() : 'The request could not be completed. Please try again.'],500);
}
