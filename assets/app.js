(() => {
  const api = 'api.php';
  let csrf = '';
  let user = null;
  let menu = [];
  let categories = [];
  let currentCategory = 'all';
  let menuRenderTimer = 0;
  let shop = {lat:14.5995,lng:120.9842,rate:15};
  let map;
  let marker;
  let coordinates = null;
  let cart = JSON.parse(localStorage.getItem('riceMotoCart') || '[]');
  const peso = value => `₱${Number(value || 0).toFixed(2)}`;
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  async function request(action, data = null, method = 'POST') {
    const options = {method, headers: {'Accept':'application/json'}};
    if (method === 'GET') {
      const query = data ? `&${new URLSearchParams(data)}` : '';
      const response = await fetch(`${api}?action=${encodeURIComponent(action)}${query}`, options);
      return handleResponse(response);
    }
    coordinates=null;
    if(marker){marker.remove();marker=null;}
    $('#delivery-address').value='';
    $('#distance-display').textContent='Choose a point on the map or find your address.';
    options.headers['Content-Type'] = 'application/json';
    options.headers['X-CSRF-Token'] = csrf;
    options.body = JSON.stringify(data || {});
    return handleResponse(await fetch(`${api}?action=${encodeURIComponent(action)}`, options));
  }
  async function handleResponse(response) {
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Something went wrong.');
    return result;
  }
  function persistCart() {
    localStorage.setItem('riceMotoCart', JSON.stringify(cart));
    $('#cart-count').textContent = cart.reduce((sum, line) => sum + line.quantity, 0);
    renderCart();
  }
  function renderMenu() {
    const query=$('#menu-search').value.trim().toLocaleLowerCase();
    const filtered = menu.filter(item=>(currentCategory==='all'||Number(item.category_id)===Number(currentCategory))&&(!query||`${item.name} ${item.description||''} ${item.category_name||''}`.toLocaleLowerCase().includes(query)));
    const grid=$('#menu-grid');
    const update=()=>{grid.innerHTML = filtered.length ? filtered.map(item => `<div class="col-sm-6 col-lg-4" data-reveal><article class="meal-card"><img class="meal-image" src="${escapeHtml(item.image_url || defaultImage(item.name))}" alt="${escapeHtml(item.name)}"><div class="meal-card-body"><span class="meal-category">${escapeHtml(item.category_name)}</span><h3 class="meal-title">${escapeHtml(item.name)}</h3><p class="meal-desc">${escapeHtml(item.description || 'Freshly cooked and made just for you.')}</p><div class="d-flex justify-content-between align-items-center"><span class="meal-price">${peso(item.price)}</span>${Number(item.is_featured) ? '<span class="badge text-bg-warning">Popular</span>' : ''}</div><div class="meal-actions"><button class="btn btn-warm btn-sm" data-add="${item.id}">Add to cart</button><button class="btn btn-outline-success btn-sm" data-customize="${item.id}">Customize</button></div></article></div>`).join('') : '<div class="col-12 text-center py-5">No meals in this category yet.</div>';};
    window.clearTimeout(menuRenderTimer);
    if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){grid.classList.remove('is-filtering');update();return;}
    grid.classList.add('is-filtering');
    menuRenderTimer=window.setTimeout(()=>{update();grid.classList.remove('is-filtering');},110);
  }
  function renderFeatured() {
    const featured=menu.filter(item=>Number(item.is_featured)).slice(0,3);
    const items=(featured.length?featured:menu.slice(0,3));
    $('#featured-menu-grid').innerHTML=items.map((item,index)=>`<div class="col-md-6 col-lg-4" data-reveal><article class="featured-card"><div class="featured-card__image"><img src="${escapeHtml(item.image_url || defaultImage(item.name))}" alt="${escapeHtml(item.name)}" loading="lazy"><span>${String(index+1).padStart(2,'0')} / ${['SIGNATURE','BEST SELLER','COMFORT CLASSIC'][index]}</span></div><div class="featured-card__body"><div class="featured-card__meta"><span>${escapeHtml(item.category_name)}</span><strong>${peso(item.price)}</strong></div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.description || 'Freshly cooked and made just for you.')}</p><div class="featured-card__pills"><span>Made fresh</span><span>Filipino favorite</span></div><button class="btn btn-warm btn-sm mt-3" data-add="${item.id}">Add to cart <span aria-hidden="true">→</span></button></div></article></div>`).join('');
  }
  function defaultImage(name) {
    const images = {'Chicken Inasal Rice':'photo-1512058564366-18510be2db19','Pork Adobo Rice':'photo-1547592180-85f173990554','Beef Tapa Rice':'photo-1512621776951-a57141f2eefd','Crispy Chicken Combo':'photo-1562967914-608f82629710','Extra Garlic Rice':'photo-1516684732162-798a0062be99','Fried Egg':'photo-1525351484163-7529414344d8'};
    return `https://images.unsplash.com/${images[name] || images['Chicken Inasal Rice']}?auto=format&fit=crop&w=800&q=80`;
  }
  function renderCategories() {
    $('#category-filters').innerHTML = `<button class="btn btn-sm btn-outline-success active" data-category="all" aria-pressed="true">Everything</button>` + categories.map(c => `<button class="btn btn-sm btn-outline-success" data-category="${c.id}" aria-pressed="false">${escapeHtml(c.name)}</button>`).join('');
  }
  function addToCart(id, options = {}) {
    const item = menu.find(entry => Number(entry.id) === Number(id));
    if (!item) return;
    const key = `${id}:${JSON.stringify(options)}`;
    const found = cart.find(line => line.key === key);
    if (found) found.quantity++;
    else cart.push({key,menu_item_id:Number(id),name:item.name,price:Number(item.price),quantity:1,options});
    persistCart();
  }
  function renderCart() {
    $('#cart-lines').innerHTML = cart.length ? cart.map((line,index) => `<div class="cart-line"><div><strong>${escapeHtml(line.name)}</strong><small>${escapeHtml(Object.values(line.options || {}).filter(Boolean).join(' · ') || 'Made fresh for you')}</small><div class="d-flex align-items-center gap-2 mt-2"><button class="btn btn-sm btn-outline-secondary" data-qty="${index}" data-delta="-1">−</button><span>${line.quantity}</span><button class="btn btn-sm btn-outline-secondary" data-qty="${index}" data-delta="1">+</button></div></div><div class="text-end"><strong>${peso(line.price * line.quantity)}</strong><button class="d-block ms-auto" data-remove="${index}">Remove</button></div></div>`).join('') : '<p class="text-muted text-center py-5">Your cart is hungry. Add a meal to get started.</p>';
    $('#cart-subtotal').textContent = peso(cart.reduce((sum,line) => sum + line.price * line.quantity,0));
    $('#cart-count').textContent = cart.reduce((sum,line) => sum + line.quantity,0);
  }
  function setAuthMode(mode) {
    const register = mode === 'register';
    $('#auth-title').textContent = register ? 'Create your account' : 'Log in';
    $('#auth-form [name=mode]').value = mode;
    $('#register-name-wrap').classList.toggle('d-none',!register);
    $('#register-phone-wrap').classList.toggle('d-none',!register);
    $('#auth-form [name=password]').setAttribute('autocomplete',register?'new-password':'current-password');
    $('#auth-switch').textContent = register ? 'Already have an account? Log in' : 'New here? Create an account';
  }
  function showError(element, error) {
    element.textContent = error.message || String(error);
    element.classList.remove('d-none');
  }
  async function loadOrders(target) {
    try {
      const {orders} = await request('my_orders',null,'GET');
      renderOrders(orders,target);
    } catch (error) {
      showError($('#dashboard-message'),error);
      $('#dashboard-message').classList.remove('d-none');
    }
  }
  function nextStatus(status,type) {
    const next = {Pending:'Preparing',Preparing:'Ready',Ready:type==='delivery'?'Out for Delivery':'Completed','Out for Delivery':'Completed'};
    return next[status] || null;
  }
  function renderOrders(orders,target) {
    const node = $(target);
    if (!orders.length) { node.innerHTML = '<div class="col-12"><div class="panel-card text-muted">No orders here yet.</div></div>'; return; }
    node.innerHTML = orders.map(order => {
      const items = (order.items || []).map(line => `${line.quantity}× ${escapeHtml(line.item_name)}`).join(', ');
      const allowedToManage = user && ['owner','staff'].includes(user.role);
      const riderCanManage = user && user.role === 'rider' && Number(order.rider_id) === Number(user.id);
      const next = nextStatus(order.status,order.order_type);
      const statusButton = next && (allowedToManage || riderCanManage) ? `<button class="btn btn-sm btn-warm mt-2" data-status="${escapeHtml(order.public_id)}" data-next="${escapeHtml(next)}">Mark ${escapeHtml(next)}</button>` : '';
      const assign = allowedToManage && order.order_type==='delivery' && ['Ready','Out for Delivery'].includes(order.status) ? `<div class="input-group input-group-sm mt-2"><select class="form-select" id="rider-${escapeHtml(order.public_id)}"><option value="">Assign rider…</option></select><button class="btn btn-outline-success" data-assign="${escapeHtml(order.public_id)}">Assign</button></div>` : '';
      const route=order.latitude&&order.longitude?`<a class="small text-success" target="_blank" rel="noreferrer" href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${encodeURIComponent(shop.lat)},${encodeURIComponent(shop.lng)}%3B${encodeURIComponent(order.latitude)},${encodeURIComponent(order.longitude)}">Open route in map ↗</a>`:'';
      const options=(order.items||[]).map(line=>{try{return Object.values(JSON.parse(line.options_json||'{}')).filter(Boolean).join(' · ')}catch{return ''}}).filter(Boolean).join(', ');
      return `<div class="col-md-6 col-xl-4"><article class="order-card"><div class="d-flex justify-content-between align-items-start gap-2"><h4>${escapeHtml(order.public_id)}</h4><span class="status-pill">${escapeHtml(order.status)}</span></div><p>${escapeHtml(order.order_type)} · ${new Date(order.created_at).toLocaleString()}</p><p>${escapeHtml(items || 'Order items')}${options?`<br><small>${escapeHtml(options)}</small>`:''}</p><p><strong>${peso(order.total)}</strong> · ${escapeHtml(order.customer_name || 'Customer')}</p>${order.delivery_address?`<p>📍 ${escapeHtml(order.delivery_address)}</p>${route}`:''}${order.rider_name?`<p>Rider: ${escapeHtml(order.rider_name)}</p>`:''}${statusButton}${assign}</article></div>`;
    }).join('');
    if (user && ['owner','staff'].includes(user.role)) loadRiders(orders);
  }
  async function loadRiders(orders) {
    const targets = orders.filter(o=>o.order_type==='delivery'&&['Ready','Out for Delivery'].includes(o.status));
    if (!targets.length) return;
    try {
      const {riders} = await request('riders',null,'GET');
      targets.forEach(order => {
        const select = $(`#rider-${CSS.escape(order.public_id)}`);
        if (select) select.innerHTML = '<option value="">Assign rider…</option>' + riders.map(r=>`<option value="${r.id}" ${Number(r.id)===Number(order.rider_id)?'selected':''}>${escapeHtml(r.name)}</option>`).join('');
      });
      $('#geocode-button').addEventListener('click',async()=>{
        const address=$('#delivery-address').value.trim();
        if(address.length<5){$('#distance-display').textContent='Enter a complete address first.';return;}
        const button=$('#geocode-button');button.disabled=true;
        try{
          const response=await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ph&q=${encodeURIComponent(address)}`);
          if(!response.ok)throw new Error('Address search is temporarily unavailable.');
          const matches=await response.json();
          if(!matches.length)throw new Error('Address not found. Pin the destination directly on the map.');
          const lat=Number(matches[0].lat),lng=Number(matches[0].lon);
          setDestination(lat,lng);map.setView([lat,lng],15);$('#distance-display').textContent=`${matches[0].display_name} · ${haversine(lat,lng).toFixed(2)} km from shop`;
        }catch(error){$('#distance-display').textContent=error.message;}finally{button.disabled=false;}
      });
    } catch (error) { console.error(error); }
  }
  async function refreshDashboard() {
    if (!user) return;
    $('#dashboard').classList.remove('d-none');
    $('#dashboard-title').textContent = `${user.role === 'owner'?'Owner':user.role === 'staff'?'Kitchen':user.role === 'rider'?'Rider':'Customer'} dashboard`;
    const section = user.role==='owner'?'owner-dashboard':user.role==='staff'?'staff-dashboard':user.role==='rider'?'rider-dashboard':'customer-dashboard';
    $(`#${section}`).classList.remove('d-none');
    if (user.role==='owner') {
      await Promise.all([loadOrders('#owner-orders'),loadAnalytics(),loadManagement()]);
    } else if (user.role==='staff') await loadOrders('#staff-orders');
    else if (user.role==='rider') await loadOrders('#rider-orders');
    else await loadOrders('#customer-orders');
  }
  async function loadAnalytics() {
    try {
      const result = await request('analytics',null,'GET');
      const sales = result.sales;
      $('#analytics-cards').innerHTML = [['Today',sales.daily],['Last 7 days',sales.weekly],['This month',sales.monthly]].map(([label,value])=>`<div class="col-md-4"><div class="panel-card"><small class="text-muted">${label} sales</small><div class="fs-4 fw-bold">${peso(value)}</div></div></div>`).join('');
      $('#top-items').innerHTML = result.top.length ? result.top.map(item=>`<div class="d-flex justify-content-between py-2 border-bottom"><span>${escapeHtml(item.item_name)}</span><strong>${item.sold} sold</strong></div>`).join('') : '<p class="text-muted">Sales data will appear here.</p>';
      $('#forecast').innerHTML = `<p>Expected orders in the next 7 days: <strong>${result.forecast.next_7_day_orders}</strong></p><p>Daily historical average: <strong>${result.forecast.daily_orders_average}</strong> orders</p><div class="mt-3"><strong>Indicative ingredient needs, next 7 days</strong>${result.forecast.ingredient_needs.map(item=>`<div class="d-flex justify-content-between border-bottom py-1 small"><span>${escapeHtml(item.name)}</span><span>${item.next_7_days} ${escapeHtml(item.unit)}</span></div>`).join('')}</div>`;
      renderInventory(result.ingredients);
      $('#activity-list').innerHTML = result.logs.map(log=>`<div class="py-2 border-bottom small"><strong>${escapeHtml(log.action)}</strong> · ${escapeHtml(log.details || '')}<span class="text-muted"> — ${escapeHtml(log.user_name || 'System')} · ${new Date(log.created_at).toLocaleString()}</span></div>`).join('') || '<p class="text-muted">No activity yet.</p>';
    } catch(error) { showError($('#dashboard-message'),error); $('#dashboard-message').classList.remove('d-none'); }
  }
  function renderInventory(ingredients) {
    $('#inventory-list').innerHTML = `<table class="table table-sm align-middle"><thead><tr><th>Ingredient</th><th>On hand</th><th>Low at</th></tr></thead><tbody>${ingredients.map(i=>`<tr><td>${escapeHtml(i.name)}</td><td class="${Number(i.stock_quantity)<=Number(i.low_stock_threshold)?'low-stock':''}">${Number(i.stock_quantity).toFixed(3)} ${escapeHtml(i.unit)}</td><td>${Number(i.low_stock_threshold)} ${escapeHtml(i.unit)}</td></tr>`).join('')}</tbody></table>`;
    $('#restock-ingredient').innerHTML = '<option value="">Choose ingredient…</option>' + ingredients.map(i=>`<option value="${i.id}">${escapeHtml(i.name)} (${escapeHtml(i.unit)})</option>`).join('');
  }
  async function loadManagement() {
    try {
      const data=await request('management_data',null,'GET');
      renderManagement(data);
    } catch(error) { showError($('#dashboard-message'),error);$('#dashboard-message').classList.remove('d-none'); }
  }
  function renderManagement(data) {
    window.riceMotoManagement=data;
    categories=data.categories.filter(category=>data.menu.some(item=>Number(item.category_id)===Number(category.id)&&Number(item.is_available)===1&&data.recipes.some(recipe=>Number(recipe.menu_item_id)===Number(item.id))));
    menu=data.menu.filter(item=>Number(item.is_available)===1&&data.recipes.some(recipe=>Number(recipe.menu_item_id)===Number(item.id)));
    renderCategories();renderMenu();renderManualItems();
    $('#menu-category').innerHTML=data.categories.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    $('#recipe-menu').innerHTML='<option value="">Choose meal…</option>'+data.menu.map(m=>`<option value="${m.id}">${escapeHtml(m.name)}</option>`).join('');
    $('#recipe-ingredients').innerHTML=data.ingredients.map(i=>{const recipe=data.recipes.find(row=>Number(row.ingredient_id)===Number(i.id)&&Number(row.menu_item_id)===Number($('#recipe-menu').value));return `<label class="recipe-row"><input type="checkbox" class="recipe-check" value="${i.id}" ${recipe?'checked':''}><span>${escapeHtml(i.name)} <small>(${escapeHtml(i.unit)})</small></span><input type="number" min="0.001" step="0.001" value="${recipe?Number(recipe.quantity_required).toFixed(3):'0.100'}" class="form-control form-control-sm recipe-amount" aria-label="Amount of ${escapeHtml(i.name)}"></label>`}).join('');
    $('#management-menu').innerHTML=data.menu.map(m=>`<div class="manage-row"><span><strong>${escapeHtml(m.name)}</strong><small>${escapeHtml(m.category_name)} · ${peso(m.price)} · ${Number(m.is_available)?'Available':'Hidden'}</small></span><span class="d-flex gap-2"><button class="btn btn-sm btn-outline-success" data-edit-menu="${m.id}">Edit</button>${Number(m.is_available)?`<button class="btn btn-sm btn-outline-danger" data-delete-menu="${m.id}">Hide</button>`:''}</span></div>`).join('')||'<p class="text-muted">No menu items.</p>';
    $('#management-categories').innerHTML=data.categories.map(c=>`<div class="manage-row"><span>${escapeHtml(c.name)}</span><span class="d-flex gap-2"><button class="btn btn-sm btn-outline-success" data-edit-category="${c.id}">Edit</button><button class="btn btn-sm btn-outline-danger" data-delete-category="${c.id}">Delete</button></span></div>`).join('');
    $('#management-users').innerHTML=`<table class="table table-sm align-middle"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>${data.users.map(u=>`<tr><td>${escapeHtml(u.name)}</td><td>${escapeHtml(u.email)}</td><td>${escapeHtml(u.role)}</td><td>${Number(u.is_active)?'Active':'Inactive'}</td><td class="text-end"><button class="btn btn-sm btn-outline-success" data-edit-user="${u.id}">Edit</button>${Number(u.is_active)&&Number(u.id)!==Number(user.id)?` <button class="btn btn-sm btn-outline-danger" data-delete-user="${u.id}">Deactivate</button>`:''}</td></tr>`).join('')}</tbody></table>`;
  }
  function renderSelectedRecipe() {
    const data=window.riceMotoManagement;
    if(!data)return;
    const menuId=Number($('#recipe-menu').value);
    $$('.recipe-row').forEach(row=>{
      const ingredientId=Number(row.querySelector('.recipe-check').value);
      const recipe=data.recipes.find(entry=>Number(entry.menu_item_id)===menuId&&Number(entry.ingredient_id)===ingredientId);
      row.querySelector('.recipe-check').checked=Boolean(recipe);
      row.querySelector('.recipe-amount').value=recipe?Number(recipe.quantity_required).toFixed(3):'0.100';
    });
  }
  function renderManualItems() {
    $('#manual-items').innerHTML = menu.map(item=>`<div class="col-md-6"><label class="d-flex align-items-center gap-2 border rounded p-2"><input class="form-check-input mt-0 manual-check" type="checkbox" value="${item.id}"><span class="flex-grow-1">${escapeHtml(item.name)} <small class="text-muted">${peso(item.price)}</small></span><input class="form-control form-control-sm manual-qty" type="number" min="1" max="50" value="1" style="width:72px" aria-label="Quantity"></label></div>`).join('');
  }
  function openCheckout() {
    if (!cart.length) return;
    if (!user) {
      setAuthMode('login');
      bootstrap.Modal.getOrCreateInstance($('#auth-modal')).show();
      $('#auth-error').textContent = 'Please log in or create an account before placing an order.';
      $('#auth-error').classList.remove('d-none');
      return;
    }
    if (user.role !== 'customer') {
      alert('Online checkout is available to customer accounts. Use the staff dashboard for an in-store order.');
      return;
    }
    bootstrap.Offcanvas.getOrCreateInstance($('#cart-drawer')).hide();
    const form = $('#checkout-form');
    form.customer_name.value = user.name || '';
    form.customer_phone.value = user.phone || '';
    updateCheckoutTotal();
    bootstrap.Modal.getOrCreateInstance($('#checkout-modal')).show();
    setTimeout(initMap,250);
  }
  function updateCheckoutTotal() {
    const subtotal = cart.reduce((sum,line)=>sum+line.price*line.quantity,0);
    const delivery = $('#order-type').value === 'delivery';
    const distance = coordinates ? haversine(coordinates.lat,coordinates.lng) : 1;
    const fee = delivery ? Math.ceil(distance*shop.rate) : 0;
    $('#checkout-total').textContent = peso(subtotal+fee);
    $('#fee-detail').textContent = delivery ? `₱${fee.toFixed(2)} delivery · ${distance.toFixed(1)} km` : 'No delivery fee';
  }
  function haversine(lat,lng) {
    const toRad = n=>n*Math.PI/180;
    const dLat=toRad(lat-shop.lat),dLng=toRad(lng-shop.lng);
    const a=Math.sin(dLat/2)**2+Math.cos(toRad(shop.lat))*Math.cos(toRad(lat))*Math.sin(dLng/2)**2;
    return 6371*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
  }
  function initMap() {
    if (map || !window.L) return;
    map=L.map('delivery-map').setView([shop.lat,shop.lng],12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
    L.marker([shop.lat,shop.lng]).addTo(map).bindPopup('Rice Mo’To Kitchen');
    map.on('click',event=>setDestination(event.latlng.lat,event.latlng.lng));
    setTimeout(()=>map.invalidateSize(),150);
  }
  function setDestination(lat,lng) {
    coordinates={lat,lng};
    if (marker) marker.remove();
    marker=L.marker([lat,lng]).addTo(map).bindPopup('Delivery destination').openPopup();
    $('#distance-display').textContent=`${haversine(lat,lng).toFixed(2)} km from shop · estimated fee ${peso(Math.ceil(haversine(lat,lng)*shop.rate))}`;
    if (!$('#delivery-address').value) $('#delivery-address').value=`Pinned location (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
    updateCheckoutTotal();
  }
  async function track(id,container) {
    try {
      const result=await request('order_status',{id:id.trim()},'GET');
      container.innerHTML=result.order?`<strong>${escapeHtml(result.order.public_id)}</strong> · <span class="status-pill">${escapeHtml(result.order.status)}</span><div class="small text-muted mt-1">${escapeHtml(result.order.order_type)} · Last update ${new Date(result.order.updated_at).toLocaleString()}</div>`:'We couldn’t find that order ID. Check it and try again.';
    } catch(error) { container.textContent=error.message; }
  }
  function chatMessage(message,who) {
    const div=document.createElement('div');div.className=`chat-bubble ${who}`;div.textContent=message;$('#chat-messages').append(div);$('#chat-messages').scrollTop=$('#chat-messages').scrollHeight;
  }
  async function init() {
    try {
      const response=await fetch(`${api}?action=bootstrap`);
      const data=await handleResponse(response);
      csrf=data.csrf;user=data.user;menu=data.menu;categories=data.categories;shop=data.shop||shop;
      renderCategories();renderMenu();renderFeatured();persistCart();renderManualItems();
      if (user) {
        $('#dashboard-link')?.classList.remove('d-none');
        await refreshDashboard();
      }
    } catch(error) {
      $('#menu-grid').innerHTML=`<div class="col-12"><div class="alert alert-danger">${escapeHtml(error.message)} Check database setup.</div></div>`;
    }
  }

  document.addEventListener('click',async event=>{
    const add=event.target.closest('[data-add]');
    if(add){addToCart(add.dataset.add);return;}
    const customize=event.target.closest('[data-customize]');
    if(customize){const spice=prompt('Spice preference? Mild, medium, or spicy?','medium');if(spice!==null)addToCart(customize.dataset.customize,{spice:spice.slice(0,40)});return;}
    const category=event.target.closest('[data-category]');
    if(category){currentCategory=category.dataset.category;$$('[data-category]').forEach(button=>{const active=button===category;button.classList.toggle('active',active);button.setAttribute('aria-pressed',String(active));});renderMenu();return;}
    const qty=event.target.closest('[data-qty]');
    if(qty){const index=Number(qty.dataset.qty);cart[index].quantity+=Number(qty.dataset.delta);if(cart[index].quantity<=0)cart.splice(index,1);persistCart();return;}
    const remove=event.target.closest('[data-remove]');
    if(remove){cart.splice(Number(remove.dataset.remove),1);persistCart();return;}
    const status=event.target.closest('[data-status]');
    if(status){try{await request('update_status',{order_id:status.dataset.status,status:status.dataset.next});await refreshDashboard();}catch(error){alert(error.message);}return;}
    const assign=event.target.closest('[data-assign]');
    if(assign){const riderId=$(`#rider-${CSS.escape(assign.dataset.assign)}`).value;if(!riderId){alert('Choose a rider first.');return;}try{await request('assign_rider',{order_id:assign.dataset.assign,rider_id:riderId});await refreshDashboard();}catch(error){alert(error.message);}return;}
    const editMenu=event.target.closest('[data-edit-menu]');
    if(editMenu){const item=window.riceMotoManagement.menu.find(row=>Number(row.id)===Number(editMenu.dataset.editMenu));const form=$('#menu-form');Object.entries({id:item.id,name:item.name,category_id:item.category_id,price:item.price,image_url:item.image_url||'',description:item.description||''}).forEach(([key,value])=>form.elements[key].value=value);form.elements.is_available.checked=Number(item.is_available)===1;form.elements.is_featured.checked=Number(item.is_featured)===1;form.scrollIntoView({behavior:'smooth',block:'center'});return;}
    const deleteMenu=event.target.closest('[data-delete-menu]');
    if(deleteMenu){if(!confirm('Hide this item from the customer menu?'))return;try{await request('menu_delete',{id:Number(deleteMenu.dataset.deleteMenu)});await refreshDashboard();}catch(error){alert(error.message);}return;}
    const editCategory=event.target.closest('[data-edit-category]');
    if(editCategory){const category=window.riceMotoManagement.categories.find(row=>Number(row.id)===Number(editCategory.dataset.editCategory));$('#category-form').elements.id.value=category.id;$('#category-form').elements.name.value=category.name;$('#category-form').scrollIntoView({behavior:'smooth',block:'center'});return;}
    const deleteCategory=event.target.closest('[data-delete-category]');
    if(deleteCategory){if(!confirm('Delete this empty category?'))return;try{await request('category_delete',{id:Number(deleteCategory.dataset.deleteCategory)});await refreshDashboard();}catch(error){alert(error.message);}return;}
    const editUser=event.target.closest('[data-edit-user]');
    if(editUser){const account=window.riceMotoManagement.users.find(row=>Number(row.id)===Number(editUser.dataset.editUser));const form=$('#user-form');['id','name','email','role','phone'].forEach(key=>form.elements[key].value=account[key]||'');form.elements.password.value='';form.elements.is_active.checked=Number(account.is_active)===1;form.scrollIntoView({behavior:'smooth',block:'center'});return;}
    const deleteUser=event.target.closest('[data-delete-user]');
    if(deleteUser){if(!confirm('Deactivate this account?'))return;try{await request('user_delete',{id:Number(deleteUser.dataset.deleteUser)});await refreshDashboard();}catch(error){alert(error.message);}}
  });
  $('#auth-modal').addEventListener('show.bs.modal',event=>{if(event.relatedTarget?.dataset.authMode)setAuthMode(event.relatedTarget.dataset.authMode);});
  $('#auth-switch').addEventListener('click',()=>setAuthMode($('#auth-form [name=mode]').value==='login'?'register':'login'));
  $('#auth-form').addEventListener('submit',async event=>{
    event.preventDefault();const form=event.currentTarget;const mode=form.mode.value;
    try{const result=await request(mode==='register'?'register':'login',Object.fromEntries(new FormData(form)));user=result.user;csrf=result.csrf;bootstrap.Modal.getOrCreateInstance($('#auth-modal')).hide();location.reload();}
    catch(error){showError($('#auth-error'),error);}
  });
  $('#logout-button')?.addEventListener('click',async()=>{try{await request('logout',{});}catch(error){console.error('Logout request failed:',error);}finally{location.reload();}});
  $$('#mainNav a').forEach(link=>link.addEventListener('click',()=>{
    if(window.matchMedia('(max-width: 767.98px)').matches)bootstrap.Collapse.getOrCreateInstance($('#mainNav')).hide();
  }));
  $$('#mainNav [data-bs-toggle="modal"], #mainNav [data-bs-toggle="offcanvas"]').forEach(button=>button.addEventListener('click',()=>{
    if(window.matchMedia('(max-width: 767.98px)').matches)bootstrap.Collapse.getOrCreateInstance($('#mainNav')).hide();
  }));
  $('#checkout-button').addEventListener('click',openCheckout);
  $('#order-type').addEventListener('change',()=>{$('#delivery-fields').classList.toggle('d-none',$('#order-type').value!=='delivery');updateCheckoutTotal();});
  $('#delivery-address').addEventListener('input',()=>{
    if(coordinates){coordinates=null;if(marker){marker.remove();marker=null;}$('#distance-display').textContent='Address changed. Find it again or pin the updated destination.';updateCheckoutTotal();}
  });
  $('#checkout-form').addEventListener('submit',async event=>{
    event.preventDefault();
    const form=event.currentTarget;const data=Object.fromEntries(new FormData(form));
    if(data.order_type==='delivery'&&!coordinates){showError($('#checkout-error'),new Error('Pin or find your delivery address on the map before ordering.'));return;}
    data.items=cart.map(line=>({menu_item_id:line.menu_item_id,quantity:line.quantity,options:line.options}));
    if(coordinates){data.latitude=coordinates.lat;data.longitude=coordinates.lng;}
    $('#place-order-button').disabled=true;
    try{
      const result=await request('create_order',data);cart=[];persistCart();bootstrap.Modal.getOrCreateInstance($('#checkout-modal')).hide();
      alert(`Order ${result.order_id} placed! Total ${peso(result.total)}. Save your order ID to track it.`);
      await refreshDashboard();
    }catch(error){showError($('#checkout-error'),error);}finally{$('#place-order-button').disabled=false;}
  });
  $('#locate-button').addEventListener('click',()=>{
    if(!navigator.geolocation){$('#distance-display').textContent='Location is not available in this browser.';return;}
    navigator.geolocation.getCurrentPosition(position=>setDestination(position.coords.latitude,position.coords.longitude),()=>{$('#distance-display').textContent='Could not access location. Click the map to pin your address.';});
  });
  $('#delivery-map').addEventListener('click',initMap);
  $('#checkout-modal').addEventListener('shown.bs.modal',initMap);
  $('#track-form').addEventListener('submit',event=>{event.preventDefault();track($('#track-id').value,$('#track-result'));});
  $('#menu-search').addEventListener('input',renderMenu);
  $('#chat-launcher').addEventListener('click',()=>$('#chat-help').classList.add('open'));
  $('#chat-close').addEventListener('click',()=>$('#chat-help').classList.remove('open'));
  $('#chat-form').addEventListener('submit',async event=>{
    event.preventDefault();const input=$('#chat-input');const message=input.value.trim();if(!message)return;
    chatMessage(message,'user');input.value='';
    try{const result=await request('chat',{message});chatMessage(result.reply,'bot');}catch(error){chatMessage(error.message,'bot');}
  });
  $('#manual-form').addEventListener('submit',async event=>{
    event.preventDefault();const form=event.currentTarget;
    const items=$$('.manual-check:checked').map(check=>({menu_item_id:Number(check.value),quantity:Number(check.closest('label').querySelector('.manual-qty').value)}));
    if(!items.length){showError($('#manual-error'),new Error('Select at least one menu item.'));return;}
    const data=Object.fromEntries(new FormData(form));data.items=items;
    try{const result=await request('manual_order',data);bootstrap.Modal.getOrCreateInstance($('#manual-order-modal')).hide();form.reset();alert(`Order ${result.order_id} created. Inventory has been updated.`);await refreshDashboard();}
    catch(error){showError($('#manual-error'),error);}
  });
  $('#restock-form').addEventListener('submit',async event=>{
    event.preventDefault();
    try{await request('restock',Object.fromEntries(new FormData(event.currentTarget)));event.currentTarget.reset();await loadAnalytics();}
    catch(error){showError($('#dashboard-message'),error);$('#dashboard-message').classList.remove('d-none');}
  });
  $('#menu-form').addEventListener('submit',async event=>{
    event.preventDefault();const form=event.currentTarget;const data=Object.fromEntries(new FormData(form));data.is_available=form.elements.is_available.checked;data.is_featured=form.elements.is_featured.checked;
    try{await request('menu_save',data);form.reset();form.elements.id.value='';form.elements.is_available.checked=true;await refreshDashboard();}catch(error){alert(error.message);}
  });
  $('#menu-reset').addEventListener('click',()=>{$('#menu-form').reset();$('#menu-form').elements.id.value='';});
  $('#category-form').addEventListener('submit',async event=>{
    event.preventDefault();try{await request('category_save',Object.fromEntries(new FormData(event.currentTarget)));event.currentTarget.reset();await refreshDashboard();}catch(error){alert(error.message);}
  });
  $('#recipe-form').addEventListener('submit',async event=>{
    event.preventDefault();const recipe=$$('.recipe-check:checked').map(check=>({ingredient_id:Number(check.value),quantity_required:Number(check.closest('.recipe-row').querySelector('.recipe-amount').value)}));
    try{await request('recipe_save',{menu_item_id:Number(event.currentTarget.menu_item_id.value),recipe});await loadManagement();alert('Recipe saved.');}catch(error){alert(error.message);}
  });
  $('#user-form').addEventListener('submit',async event=>{
    event.preventDefault();const form=event.currentTarget;const data=Object.fromEntries(new FormData(form));data.is_active=form.elements.is_active.checked;
    try{await request('user_save',data);form.reset();form.elements.id.value='';form.elements.is_active.checked=true;await refreshDashboard();}catch(error){alert(error.message);}
  });
  $('#user-reset').addEventListener('click',()=>{$('#user-form').reset();$('#user-form').elements.id.value='';$('#user-form').elements.is_active.checked=true;});
  $('#recipe-menu').addEventListener('change',renderSelectedRecipe);

  init();
  window.setInterval(()=>{if(user&&!document.hidden)refreshDashboard();},30000);
})();
