<?php
require __DIR__ . '/config.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#183c2b">
  <title>Rice Mo’To — Rice meals made to move you</title>
  <link rel="stylesheet" href="assets/css/preloader.css">
  <script>document.documentElement.classList.add('rice-preloader-enabled');</script>
  <script src="assets/js/preloader.js" defer></script>
  <script>tailwind = {config: {corePlugins: {preflight: false}}};</script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="assets/app.css">
  <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<div id="main-content">
<nav class="navbar navbar-expand-md navbar-dark site-nav sticky-top">
  <div class="container position-relative">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#"><span class="brand-mark">R</span> Rice Mo’To</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <div class="navbar-nav ms-auto align-items-md-center gap-md-2">
        <a class="nav-link" href="#menu">Menu</a><a class="nav-link" href="#how">How it works</a><a class="nav-link" href="#track">Track order</a><a class="nav-link" href="#chat-help">Chatbot</a>
        <div class="nav-actions">
        <?php if ($user): ?>
          <a class="nav-link" href="#dashboard" id="dashboard-link">Dashboard</a>
          <span class="nav-link text-white-50 small"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></span>
          <button class="btn btn-sm btn-outline-light" id="logout-button">Log out</button>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#auth-modal">Log in</button>
          <button class="btn btn-sm btn-warm" data-bs-toggle="modal" data-bs-target="#auth-modal" data-auth-mode="register">Sign up</button>
        <?php endif; ?>
        <button class="btn btn-sm btn-light cart-nav" data-bs-toggle="offcanvas" data-bs-target="#cart-drawer">Cart <span class="badge rounded-pill text-bg-success" id="cart-count">0</span></button>
        </div>
      </div>
    </div>
  </div>
</nav>

<main>
  <section class="hero-section">
    <div class="container py-5 py-lg-6">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="eyebrow" data-reveal><span class="status-dot"></span> HOT RICE MEALS · MADE FRESH</div>
          <h1 class="hero-title mt-3" data-reveal>Big comfort.<br><span>Fast delivery.</span></h1>
          <p class="hero-copy mt-3" data-reveal>Your favorite Filipino rice meals, cooked fresh and delivered hot. Order for delivery or takeout, then follow every step from kitchen to your door.</p>
          <div class="d-flex flex-wrap gap-3 mt-4" data-reveal><a class="btn btn-warm btn-lg" href="#menu">Order now <span aria-hidden="true">→</span></a><a class="btn btn-outline-dark btn-lg" href="#how">How it works</a></div>
          <div class="hero-proof mt-4" data-reveal><span>★★★★★</span> Freshly cooked, locally loved <i></i> Real-time order updates</div>
        </div>
        <div class="col-lg-6"><div class="hero-image-card" data-reveal="fade-left">
          <img data-parallax src="https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=1100&q=85" alt="A freshly prepared rice meal with grilled chicken">
          <div class="floating-note"><span class="note-icon">♨</span><div><strong>Hot off the kitchen</strong><small>Made fresh when you order</small></div></div>
          <div class="hero-sticker">RICE<br><b>MO’TO</b><small>GOOD FOOD. GOOD MOOD.</small></div>
        </div></div>
      </div>
    </div>
  </section>

  <section class="trust-strip"><div class="container"><div class="row g-3 text-center" data-reveal-stagger><div class="col-4" data-reveal><strong>Fresh</strong><small>Made to order</small></div><div class="col-4" data-reveal><strong>Fast</strong><small>Kitchen-to-door tracking</small></div><div class="col-4" data-reveal><strong>Easy</strong><small>Delivery or takeout</small></div></div></div></section>

  <section class="rice-ribbon" aria-label="Rice Mo’To favorites">
    <div class="rice-ribbon__track">
      <div class="rice-ribbon__group"><span>Sisig Rice</span><i>✳</i><span>Adobo Bowl</span><i>✳</i><span>Garlic Rice</span><i>✳</i><span>Longsilog</span><i>✳</i><span>Fast Delivery</span><i>✳</i></div>
      <div class="rice-ribbon__group" aria-hidden="true"><span>Sisig Rice</span><i>✳</i><span>Adobo Bowl</span><i>✳</i><span>Garlic Rice</span><i>✳</i><span>Longsilog</span><i>✳</i><span>Fast Delivery</span><i>✳</i></div>
    </div>
  </section>

  <section class="feature-section section-pad" id="featured" aria-labelledby="featured-title"><div class="container">
    <div class="section-heading feature-heading" data-reveal><div><div class="eyebrow">A FEW HOUSE FAVORITES</div><h2 id="featured-title">Made fresh.<br><span>Remembered fondly.</span></h2></div><p>Comforting Filipino flavors, generous portions, and little details worth slowing down for.</p></div>
    <div class="row g-4 mt-2 featured-grid" id="featured-menu-grid" data-reveal-stagger aria-live="polite"></div>
  </div></section>

  <section class="story-section section-pad"><div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-6"><div class="story-image" data-reveal="fade-left"><img data-parallax src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1000&q=85" alt="A comforting Filipino-inspired rice meal served fresh"><span class="story-image__note">GOOD FOOD, GOOD MOOD</span></div></div>
    <div class="col-lg-6"><div class="story-copy" data-reveal><div class="eyebrow">A LITTLE MORE JOY IN EVERY BITE</div><h2>Comfort food,<br><span>made with care.</span></h2><p>From the first scoop of garlic rice to the last bite, every order is prepared fresh and packed for your kind of day.</p><div class="story-pills"><span>Cooked to order</span><span>Filipino favorites</span><span>Delivery or takeout</span></div><a class="btn btn-warm mt-4" href="#menu">Find your favorite <span aria-hidden="true">→</span></a></div></div>
  </div></div></section>

  <section class="section-pad menu-section" id="menu"><div class="container">
    <div class="section-heading d-flex flex-wrap align-items-end justify-content-between gap-3" data-reveal><div><div class="eyebrow">THE GOOD STUFF</div><h2>Meals worth <span>coming back for.</span></h2><p>Real ingredients, generous portions, and all the rice you’re craving.</p></div><div class="d-flex flex-column align-items-lg-end gap-2"><input class="form-control menu-search" id="menu-search" type="search" placeholder="Search meals…" aria-label="Search meals"><div class="menu-filters d-flex flex-wrap gap-2" id="category-filters"></div></div></div>
    <div class="row g-4 mt-2" id="menu-grid" data-reveal-stagger><div class="col-12 text-center py-5 text-muted">Loading the menu…</div></div>
  </div></section>

  <section class="how-section section-pad" id="how"><div class="container" id="how-it-works">
    <div class="text-center section-heading" data-reveal><div class="eyebrow">GOOD FOOD, NO GUESSWORK</div><h2>From craving to <span>“that was so good.”</span></h2><p>Easy ordering with updates all the way through.</p></div>
    <div class="row g-4 mt-3" data-reveal-stagger>
      <div class="col-md-6 col-lg-3"><article class="step-card" data-reveal><div class="step-number">01</div><div class="step-icon">🍚</div><h3>Browse & customize</h3><p>Pick a rice meal, add your extras, and tell our kitchen how you like it.</p></article></div>
      <div class="col-md-6 col-lg-3"><article class="step-card" data-reveal><div class="step-number">02</div><div class="step-icon">📍</div><h3>Choose your handoff</h3><p>Pin your delivery spot or choose a quick pickup at the store.</p></article></div>
      <div class="col-md-6 col-lg-3"><article class="step-card" data-reveal><div class="step-number">03</div><div class="step-icon">🛵</div><h3>Follow every update</h3><p>See when we’re cooking, packing, and bringing your order your way.</p></article></div>
      <div class="col-md-6 col-lg-3"><article class="step-card" data-reveal><div class="step-number">04</div><div class="step-icon">😋</div><h3>Dig in, happily</h3><p>Get comfy. Your fresh Rice Mo’To meal is nearly there.</p></article></div>
    </div>
  </div></section>

  <section class="section-pad" id="track"><div class="container" id="track-order"><div class="row g-4 align-items-stretch">
    <div class="col-lg-7"><div class="track-card h-100" data-reveal><div class="eyebrow">NO “WHERE’S MY FOOD?” MOMENTS</div><h2>Know where your order is.</h2><p>Enter your order ID to see the latest kitchen or rider update.</p><form class="input-group track-form" id="track-form"><input class="form-control" id="track-id" placeholder="e.g. RM-A1B2C3D4" maxlength="24" required><button class="btn btn-warm" type="submit">Track order</button></form><div class="track-result mt-3" id="track-result" aria-live="polite"></div></div></div>
    <div class="col-lg-5"><div class="store-card h-100" data-reveal="fade-left"><div class="eyebrow">COME SAY HI</div><h3>Your local rice stop.</h3><p>Freshly cooked Filipino favorites, ready for pickup or delivery around Manila.</p><div class="store-detail"><span>⌖</span><div><strong>Manila, Philippines</strong><small>Rice Mo’To Kitchen</small></div></div><div class="store-detail"><span>◷</span><div><strong>Open every day</strong><small>10:00 AM – 10:00 PM</small></div></div><a class="text-link" href="https://www.openstreetmap.org/?mlat=14.5995&mlon=120.9842#map=14/14.5995/120.9842" target="_blank" rel="noreferrer">View shop area ↗</a></div></div>
  </div></div></section>

  <section class="dashboard-section section-pad d-none" id="dashboard"><div class="container">
    <div class="section-heading"><div class="eyebrow">YOUR RICE MO’TO WORKSPACE</div><h2 id="dashboard-title">Dashboard</h2><p id="dashboard-subtitle">Orders and updates in one place.</p></div>
    <div class="alert alert-info d-none" id="dashboard-message" role="status"></div>
    <div id="customer-dashboard" class="d-none"><h3 class="h5 mt-4">Your orders</h3><div id="customer-orders" class="row g-3"></div></div>
    <div id="staff-dashboard" class="d-none">
      <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 gap-2"><h3 class="h5 mb-0">Kitchen & order board</h3><button class="btn btn-warm" data-bs-toggle="modal" data-bs-target="#manual-order-modal">＋ In-store order</button></div>
      <div id="staff-orders" class="row g-3 mt-1"></div>
    </div>
    <div id="rider-dashboard" class="d-none"><h3 class="h5 mt-4">Assigned deliveries</h3><div id="rider-orders" class="row g-3"></div></div>
    <div id="owner-dashboard" class="d-none">
      <h3 class="h5 mt-4">All orders</h3><div id="owner-orders" class="row g-3"></div>
      <div id="analytics-cards" class="row g-3 mt-2"></div>
      <div class="row g-4 mt-1"><div class="col-lg-6"><div class="panel-card"><h3 class="h5">Top-selling meals</h3><div id="top-items"></div></div></div><div class="col-lg-6"><div class="panel-card"><h3 class="h5">Indicative moving-average forecast</h3><div id="forecast"></div><small class="text-muted">Historical average only; not a guarantee of future demand.</small></div></div></div>
      <div class="row g-4 mt-1"><div class="col-lg-7"><div class="panel-card"><h3 class="h5">Ingredient stock</h3><div id="inventory-list" class="table-responsive"></div></div></div><div class="col-lg-5"><div class="panel-card"><h3 class="h5">Restock ingredient</h3><form id="restock-form" class="vstack gap-2"><select class="form-select" name="ingredient_id" id="restock-ingredient" required></select><input class="form-control" name="quantity" type="number" step="0.001" min="0.001" placeholder="Quantity to add" required><input class="form-control" name="reason" placeholder="Reason (optional)"><button class="btn btn-warm">Record stock-in</button></form></div></div></div>
      <div class="panel-card mt-4"><h3 class="h5">Recent activity</h3><div id="activity-list"></div></div>
      <div class="mt-5"><div class="eyebrow">STORE ADMINISTRATION</div><h3 class="h4 mt-2">Menu, recipes & accounts</h3><p class="text-muted">Manage the live catalog, ingredient recipes, and staff or rider access.</p></div>
      <div class="row g-4 mt-1">
        <div class="col-lg-7"><div class="panel-card"><h4 class="h5">Menu item</h4><form id="menu-form" class="row g-2"><input type="hidden" name="id"><div class="col-md-6"><input class="form-control" name="name" placeholder="Meal name" required></div><div class="col-md-6"><select class="form-select" name="category_id" id="menu-category" required></select></div><div class="col-md-6"><input class="form-control" name="price" type="number" step=".01" min="0" placeholder="Price (₱)" required></div><div class="col-md-6"><input class="form-control" name="image_url" placeholder="Image URL (optional)"></div><div class="col-12"><textarea class="form-control" name="description" rows="2" placeholder="Description"></textarea></div><div class="col-12 d-flex gap-3"><label><input type="checkbox" name="is_available" checked> Available</label><label><input type="checkbox" name="is_featured"> Featured</label><button class="btn btn-sm btn-warm ms-auto">Save item</button><button type="button" class="btn btn-sm btn-outline-secondary" id="menu-reset">Clear</button></div></form><div id="management-menu" class="mt-3"></div></div></div>
        <div class="col-lg-5"><div class="panel-card"><h4 class="h5">Menu categories</h4><form id="category-form" class="d-flex gap-2"><input type="hidden" name="id"><input class="form-control" name="name" placeholder="Category name" required><button class="btn btn-sm btn-warm">Save</button></form><div id="management-categories" class="mt-3"></div></div>
          <div class="panel-card mt-4"><h4 class="h5">Recipe ingredients</h4><form id="recipe-form" class="vstack gap-2"><select class="form-select" name="menu_item_id" id="recipe-menu" required></select><div id="recipe-ingredients" class="recipe-ingredients"></div><button class="btn btn-sm btn-warm">Save recipe</button></form></div></div>
      </div>
      <div class="panel-card mt-4"><h4 class="h5">User accounts & riders</h4><form id="user-form" class="row g-2"><input type="hidden" name="id"><div class="col-md-3"><input class="form-control" name="name" placeholder="Full name" required></div><div class="col-md-3"><input class="form-control" name="email" type="email" placeholder="Email" required></div><div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div><div class="col-md-2"><select class="form-select" name="role"><option value="customer">Customer</option><option value="staff">Staff / Kitchen</option><option value="rider">Rider</option><option value="owner">Owner</option></select></div><div class="col-md-2"><input class="form-control" name="password" type="password" placeholder="Password (new/change)"></div><div class="col-12 d-flex align-items-center gap-3"><label><input type="checkbox" name="is_active" checked> Active</label><button class="btn btn-sm btn-warm">Save account</button><button class="btn btn-sm btn-outline-secondary" type="button" id="user-reset">Clear</button></div></form><div id="management-users" class="mt-3 table-responsive"></div></div>
    </div>
  </div></section>
</main>

<footer class="site-footer"><div class="container"><div class="row g-4"><div class="col-lg-5"><a class="footer-brand" href="#">Rice Mo’To</a><p>Filipino rice meals with a little extra joy in every bite.</p></div><div class="col-6 col-lg-3"><strong>Explore</strong><a href="#menu">Our menu</a><a href="#how">How it works</a><a href="#track">Track an order</a></div><div class="col-6 col-lg-4"><strong>Find us</strong><span>Manila, Philippines</span><span>Daily, 10:00 AM – 10:00 PM</span><a href="#chat-help">Ask our chat assistant</a></div></div><hr><div class="d-flex flex-wrap justify-content-between gap-2"><small>© <?= date('Y') ?> Rice Mo’To. Good food. Good mood.</small><small>Online Food Ordering & Inventory Management System</small></div></div></footer>

<button class="chat-launcher" id="chat-launcher" aria-label="Open Rice Mo’To chat">☏</button>
<aside class="chat-widget" id="chat-help" aria-label="Rice Mo’To chat assistant"><header><div><strong>Rice Mo’To assistant</strong><small>Here to help with your cravings</small></div><button id="chat-close" aria-label="Close chat">×</button></header><div class="chat-messages" id="chat-messages"><div class="chat-bubble bot">Hey! Ask me about our menu, store hours, delivery, or an order status.</div></div><form id="chat-form"><input id="chat-input" class="form-control" placeholder="Ask us anything…" maxlength="500" required><button class="btn btn-warm" aria-label="Send message">➤</button></form></aside>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cart-drawer" aria-labelledby="cart-title"><div class="offcanvas-header"><h5 class="offcanvas-title" id="cart-title">Your cart</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div><div class="offcanvas-body d-flex flex-column"><div id="cart-lines" class="flex-grow-1"></div><div class="border-top pt-3"><div class="d-flex justify-content-between fw-bold mb-3"><span>Subtotal</span><span id="cart-subtotal">₱0.00</span></div><button class="btn btn-warm w-100" id="checkout-button">Continue to checkout</button></div></div></div>

<div class="modal fade" id="auth-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><div class="eyebrow">WELCOME TO RICE MO’TO</div><h5 class="modal-title" id="auth-title">Log in</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="alert alert-danger d-none" id="auth-error"></div><form id="auth-form" class="vstack gap-3"><input type="hidden" name="mode" value="login"><div class="d-none" id="register-name-wrap"><label class="form-label">Your name</label><input class="form-control" name="name" autocomplete="name"></div><div><label class="form-label">Email</label><input class="form-control" type="email" name="email" autocomplete="email" required></div><div class="d-none" id="register-phone-wrap"><label class="form-label">Phone</label><input class="form-control" name="phone" autocomplete="tel"></div><div><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="8" autocomplete="current-password" required></div><button class="btn btn-warm w-100">Continue</button><button type="button" class="btn btn-link" id="auth-switch">New here? Create an account</button></form></div></div></div></div>

<div class="modal fade" id="checkout-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><div class="eyebrow">ALMOST THERE</div><h5 class="modal-title">Checkout</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="alert alert-danger d-none" id="checkout-error"></div><form id="checkout-form" class="row g-3"><div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="customer_name" required></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="customer_phone" required></div><div class="col-md-6"><label class="form-label">Order type</label><select class="form-select" name="order_type" id="order-type"><option value="delivery">Delivery</option><option value="takeout">Takeout</option></select></div><div class="col-md-6"><label class="form-label">Payment</label><select class="form-select" name="payment_method"><option value="cash">Cash on delivery / pickup</option><option value="online">Online (sandbox simulation)</option></select></div><div class="col-12" id="delivery-fields"><label class="form-label">Delivery address</label><input class="form-control" name="delivery_address" id="delivery-address" placeholder="Street, barangay, city"><div class="map-tools mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="locate-button">Use my location</button><button type="button" class="btn btn-sm btn-outline-secondary" id="geocode-button">Find address</button><small id="distance-display">Choose a point on the map or find your address.</small></div><div id="delivery-map"></div></div><div class="col-12"><label class="form-label">Kitchen notes / spice preference</label><textarea class="form-control" name="notes" rows="2" placeholder="Anything we should know?"></textarea></div><div class="col-12"><div class="checkout-total d-flex justify-content-between"><span>Estimated total <small id="fee-detail"></small></span><strong id="checkout-total">₱0.00</strong></div></div><div class="col-12"><button class="btn btn-warm w-100" id="place-order-button">Place order</button></div></form></div></div></div></div>

<div class="modal fade" id="manual-order-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">In-store / phone order</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="alert alert-danger d-none" id="manual-error"></div><form id="manual-form" class="row g-3"><div class="col-md-6"><label class="form-label">Customer name</label><input class="form-control" name="customer_name" required></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="customer_phone"></div><div class="col-md-6"><label class="form-label">Order type</label><select class="form-select" name="order_type"><option value="takeout">Takeout</option><option value="delivery">Delivery</option></select></div><div class="col-md-6"><label class="form-label">Payment</label><select class="form-select" name="payment_method"><option value="cash">Cash</option><option value="online">Online sandbox</option></select></div><div class="col-md-8"><label class="form-label">Delivery address (delivery only)</label><input class="form-control" name="delivery_address"></div><div class="col-md-4"><label class="form-label">Estimated distance (km)</label><input class="form-control" name="distance_km" type="number" min=".1" max="50" step=".1" placeholder="Delivery only"></div><div class="col-12"><label class="form-label">Items</label><div id="manual-items" class="row g-2"></div></div><div class="col-12"><button class="btn btn-warm w-100">Create order & deduct stock</button></div></form></div></div></div></div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/app.js" defer></script>
<script src="assets/js/animations.js" defer></script>
</body>
</html>
