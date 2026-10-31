const WHATSAPP_NUMBER = "918527479492";
const INSTAGRAM_HANDLE = "rama_aurantha";
const CART_KEY = "rama-aurantha-cart-v2";
const ORDER_KEY = "rama-aurantha-last-order";
const PROFILE_KEY = "rama-aurantha-profile-v1";
const ADDRESS_KEY = "rama-aurantha-addresses-v1";
const PRODUCTS_KEY = "rama-aurantha-products-v1";
const API_URL = "api.php";

const imagePool = [
  "photo-1603006905003-be475563bc59",
  "photo-1602874801007-bd458bb1b8b6",
  "photo-1602523961358-f9f03dd557db",
  "photo-1602874801007-bd458bb1b8b6",
  "photo-1608571423902-eed4a5ad8108",
  "photo-1603006905003-be475563bc59",
];

const starterProducts = [
  [
    "U-01",
    "Lotus Pond Urli - Mini",
    "urli",
    1299,
    ["Bestseller", "Gift-ready"],
  ],
  ["U-02", "Blooming Lotus Candle", "urli", 899, ["Floral", "Hand-poured"]],
  ["U-03", "Hibiscus Bunch Urli", "urli", 1599, ["Statement piece", "Floral"]],
  ["U-04", "Floating Lotus Urli", "urli", 1199, ["Bestseller", "Gift-ready"]],
  ["U-05", "Royal Peacock Diya Set", "urli", 1499, ["Festive", "Set of 2"]],
  ["U-06", "Sunflower Diya Set", "urli", 1099, ["Festive", "Set of 2"]],
  [
    "U-07",
    "Coral Multi-Wick Urli Trio",
    "urli",
    1899,
    ["Statement piece", "Set of 3"],
  ],
  [
    "U-08",
    "Elephant Urli Candle Pair",
    "urli",
    1399,
    ["Gift-ready", "Set of 2"],
  ],
  ["U-09", "Blossom Urli Candle", "urli", 1199, ["Floral", "Hand-poured"]],
  ["U-10", "Glitter Lotus Urli", "urli", 1299, ["Bestseller", "Festive"]],
  [
    "U-11",
    "Lotus Pond Boat Urli",
    "urli",
    1499,
    ["Statement piece", "Gift-ready"],
  ],
  [
    "U-12",
    "Royal Lotus Pond Urli Set",
    "urli",
    1999,
    ["Premium", "Gift-ready"],
  ],
  ["U-13", "Lakshmi Charan Urli", "urli", 1199, ["Festive", "Hand-poured"]],
  ["U-14", "Dry Fruit Urli Candle", "urli", 1399, ["Festive", "Gift-ready"]],
  ["S-01", "Baby's Breath Jar Candle", "soy", 649, ["Soy wax", "Bestseller"]],
  ["S-02", "Red Peony Jar Candle", "soy", 749, ["Floral", "Soy wax"]],
  ["S-03", "Pink Tulip Bouquet Candle", "soy", 899, ["Floral", "Gift-ready"]],
  ["S-04", "Daisy Trio Candle Set", "soy", 999, ["Set of 3", "Soy wax"]],
  ["S-05", "White Peony Bud Candles (Set)", "soy", 799, ["Floral", "Set of 2"]],
  ["S-06", "Embrace Couple Candle", "soy", 899, ["Gift-ready", "Hand-poured"]],
  ["S-07", "Pink Peony Candle Set", "soy", 1099, ["Floral", "Gift-ready"]],
  [
    "S-08",
    "Classic Colour Jar Candles",
    "soy",
    599,
    ["Custom colour", "Soy wax"],
  ],
  ["S-09", "Blush Pink Votive Candles", "soy", 699, ["Set of 4", "Soy wax"]],
  ["S-10", "Bubble Cube Candle", "soy", 499, ["Modern", "Bestseller"]],
  ["S-11", "Heart Wax Melts & Candle", "soy", 749, ["Gift-ready", "Soy wax"]],
  ["S-12", "Gold Leaf Votive Candles", "soy", 799, ["Set of 4", "Festive"]],
  ["S-13", "Rose Bouquet Ball Candles", "soy", 999, ["Floral", "Gift-ready"]],
  ["S-14", "Lilac Peony Jar Candle", "soy", 749, ["Floral", "Soy wax"]],
  ["S-15", "Heart Blossom Candles", "soy", 899, ["Set of 2", "Gift-ready"]],
  ["S-16", "Daisy Gift Box Candles", "soy", 1199, ["Gift-ready", "Set of 4"]],
  ["S-17", "Woven Heart Candles", "soy", 899, ["Hand-poured", "Gift-ready"]],
].map(([code, name, category, price, tags], index) => ({
  id: code.toLowerCase(),
  code,
  name,
  category,
  price,
  stockQuantity: 0,
  tags,
  image: `https://images.unsplash.com/${imagePool[index % imagePool.length]}?auto=format&fit=crop&w=960&q=85`,
}));

const app = document.querySelector("#app");
const cartCount = document.querySelector("#cart-count");
const money = (amount) =>
  new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(amount);
const safe = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (char) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      })[char],
  );

function readStored(key, fallback) {
  try {
    return JSON.parse(localStorage.getItem(key)) || fallback;
  } catch {
    return fallback;
  }
}

let products = readStored(PRODUCTS_KEY, starterProducts);
let cart = readStored(CART_KEY, []);
let profile = readStored(PROFILE_KEY, null);
let addresses = readStored(ADDRESS_KEY, []);
let filters = { category: "all", search: "", sort: "featured", tag: "all" };
let apiReady = false;
let backendError = "";
let adminAuthenticated = false;
let adminRole = "";
let adminUsername = "";
let csrfToken = "";
let serverCatalogEmpty = false;

async function apiRequest(resource, action = "", options = {}) {
  const headers = { Accept: "application/json" };
  if (options.body !== undefined) headers["Content-Type"] = "application/json";
  if (options.authenticated) headers["X-CSRF-Token"] = csrfToken;
  const query = new URLSearchParams({ resource });
  if (action) query.set("action", action);
  const response = await fetch(`${API_URL}?${query}`, {
    method: options.method || "GET",
    credentials: "same-origin",
    headers,
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(result.error || `Request failed (${response.status}).`);
  return result;
}

function cacheProducts() {
  try {
    localStorage.setItem(PRODUCTS_KEY, JSON.stringify(products));
  } catch {
    // The server remains the source of truth if the browser cache is full.
  }
}

async function loadAdminCatalog() {
  const result = await apiRequest("products");
  serverCatalogEmpty = result.products.length === 0;
  if (adminRole === "owner" && serverCatalogEmpty && products.length) {
    const imported = await apiRequest("products", "import", {
      method: "POST",
      authenticated: true,
      body: { products },
    });
    products = imported.products;
    serverCatalogEmpty = products.length === 0;
  } else {
    products = result.products;
  }
  cacheProducts();
}

async function initializeBackend() {
  try {
    const result = await apiRequest("products");
    apiReady = true;
    serverCatalogEmpty = result.products.length === 0;
    if (result.products.length) {
      products = result.products;
      cacheProducts();
    }
    const session = await apiRequest("admin", "session");
    adminAuthenticated = session.authenticated && Boolean(session.role);
    adminRole = adminAuthenticated ? session.role : "";
    adminUsername = adminAuthenticated ? session.username : "";
    csrfToken = session.csrf || "";
    if (adminAuthenticated) await loadAdminCatalog();
  } catch (error) {
    backendError = error.message;
    apiReady = false;
  }
  renderRoute();
}

function saveProfile() {
  localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
}

function saveAddresses() {
  localStorage.setItem(ADDRESS_KEY, JSON.stringify(addresses));
}

function addAddress(address) {
  const duplicate = addresses.some((saved) =>
    ["address", "city", "state", "pincode"].every(
      (key) => saved[key].toLowerCase() === address[key].toLowerCase(),
    ),
  );
  if (!duplicate) addresses.push(address);
  saveAddresses();
}

function persistCart() {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartCount();
}

function updateCartCount() {
  const count = cart.reduce((total, item) => total + item.quantity, 0);
  cartCount.textContent = count;
  cartCount.setAttribute("aria-label", `${count} items in cart`);
}

function routeParts() {
  return (location.hash.replace(/^#\/?/, "") || "shop").split("/");
}

function navigate(path) {
  location.hash = `/${path}`;
}

function productById(id) {
  return products.find((product) => product.id === id);
}

function addToCart(id, quantity = 1) {
  const product = productById(id);
  if (!product) return;
  const existing = cart.find((item) => item.id === id);
  const stockQuantity = Number(product.stockQuantity) || 0;
  const nextQuantity = (existing?.quantity || 0) + quantity;
  if (nextQuantity > stockQuantity) {
    const toast = document.querySelector("#cart-toast");
    toast.textContent = stockQuantity
      ? `Only ${stockQuantity} unit(s) of ${product.name} are in stock`
      : `${product.name} is currently out of stock`;
    toast.classList.add("visible");
    window.clearTimeout(addToCart.toastTimer);
    addToCart.toastTimer = window.setTimeout(() => toast.classList.remove("visible"), 2200);
    return;
  }
  if (existing) existing.quantity = nextQuantity;
  else cart.push({ id, quantity });
  persistCart();
  const button = document.querySelector(`[data-add="${id}"]`);
  if (button?.classList.contains("add-button")) {
    button.classList.add("is-added");
    button.textContent = "✓";
    button.setAttribute("aria-label", `${product.name} added to bag`);
    window.setTimeout(() => {
      button.classList.remove("is-added");
      button.textContent = "+";
      button.setAttribute("aria-label", `Add ${product.name} to cart`);
    }, 900);
  }
  const toast = document.querySelector("#cart-toast");
  toast.textContent = `${product.name} added to bag`;
  toast.classList.add("visible");
  window.clearTimeout(addToCart.toastTimer);
  addToCart.toastTimer = window.setTimeout(() => toast.classList.remove("visible"), 1800);
}

function cartDetails() {
  return cart
    .map((item) => ({
      ...item,
      product: productById(item.id),
    }))
    .filter((item) => item.product);
}

function subtotal() {
  return cartDetails().reduce(
    (total, item) => total + item.product.price * item.quantity,
    0,
  );
}

function shipping() {
  return subtotal() === 0 || subtotal() >= 1499 ? 0 : 99;
}

function orderSummary() {
  return `Subtotal: ${money(subtotal())}\nDelivery: ${shipping() ? money(shipping()) : "Free"}\nTotal: ${money(subtotal() + shipping())}`;
}

function productCard(product, index) {
  return `<article class="product-card">
    <a class="product-image" href="#/product/${product.id}" aria-label="View ${safe(product.name)}">
      <img src="${product.image}" alt="${safe(product.name)}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=960&q=80'">
      <span class="image-index">${String(index + 1).padStart(2, "0")}</span>
    </a>
    <div class="product-copy">
      <div class="product-meta"><span>${product.code}</span><span>${product.category === "urli" ? "Urli candle" : "Soy wax"}</span></div>
      <h3><a href="#/product/${product.id}">${safe(product.name)}</a></h3>
      <div class="tag-list">${product.tags.map((tag) => `<span class="tag">${safe(tag)}</span>`).join("")}</div>
      <div class="product-buy"><div><strong>${money(product.price)}</strong><small class="stock-label">${Number(product.stockQuantity) > 0 ? `${Number(product.stockQuantity)} in stock` : "Out of stock"}</small></div><button class="icon-button add-button" type="button" data-add="${product.id}" aria-label="Add ${safe(product.name)} to cart" title="Add to cart" ${Number(product.stockQuantity) < 1 ? "disabled" : ""}>+</button></div>
    </div>
  </article>`;
}

function visibleProducts() {
  const query = filters.search.trim().toLowerCase();
  let result = products.filter((product) => {
    const matchesCategory =
      filters.category === "all" || product.category === filters.category;
    const matchesQuery =
      !query ||
      `${product.name} ${product.code} ${product.tags.join(" ")}`
        .toLowerCase()
        .includes(query);
    const matchesTag =
      filters.tag === "all" || product.tags.includes(filters.tag);
    return matchesCategory && matchesQuery && matchesTag;
  });
  if (filters.sort === "price-low")
    result = [...result].sort((a, b) => a.price - b.price);
  if (filters.sort === "price-high")
    result = [...result].sort((a, b) => b.price - a.price);
  if (filters.sort === "name")
    result = [...result].sort((a, b) => a.name.localeCompare(b.name));
  return result;
}

function renderShop() {
  const shown = visibleProducts();
  app.innerHTML = `<section class="shop-intro">
    <div class="eyebrow">The candle atelier · Est. with care</div>
    <h1>Small flames.<br><em>Lasting impressions.</em></h1>
    <p>Hand-poured candles for slow evenings, thoughtful gifting and the moments worth keeping.</p>
    <a class="text-link" href="#/shop/collection">Shop the collection <span aria-hidden="true">↓</span></a>
    <div class="shop-note"><span>01 / 03</span><span>Made to order in India</span></div>
    <div class="hero-photo" role="img" aria-label="Warmly lit handcrafted candle" ></div>
  </section>
  <section class="promise-bar" aria-label="Store benefits"><span>Hand-poured, made to order</span><span>Custom colour & fragrance</span><span>Complimentary delivery over ₹1,499</span></section>
  <section class="collection-section" id="collection">
    <div class="section-heading"><div><div class="eyebrow">Find your little ritual</div><h2>The collection</h2></div><p>Designed by hand. Made for your home.</p></div>
    <div class="catalog-tools">
      <div class="category-tabs" role="group" aria-label="Filter by collection">
        <button type="button" data-category="all" class="${filters.category === "all" ? "selected" : ""}">All <span>${products.length}</span></button>
        <button type="button" data-category="urli" class="${filters.category === "urli" ? "selected" : ""}">Urli <span>${products.filter((product) => product.category === "urli").length}</span></button>
        <button type="button" data-category="soy" class="${filters.category === "soy" ? "selected" : ""}">Soy wax <span>${products.filter((product) => product.category === "soy").length}</span></button>
      </div>
      <div class="tool-fields"><label class="search-field"><span class="sr-only">Search products</span><input type="search" id="product-search" placeholder="Search candles" value="${safe(filters.search)}"></label>
        <label class="select-field"><span class="sr-only">Sort products</span><select id="product-sort"><option value="featured" ${filters.sort === "featured" ? "selected" : ""}>Featured</option><option value="price-low" ${filters.sort === "price-low" ? "selected" : ""}>Price: low to high</option><option value="price-high" ${filters.sort === "price-high" ? "selected" : ""}>Price: high to low</option><option value="name" ${filters.sort === "name" ? "selected" : ""}>Name</option></select></label>
      </div>
    </div>
    <div class="tag-filters" aria-label="Filter by product tag"><span>Popular:</span>${["all", "Bestseller", "Gift-ready", "Floral", "Festive"].map((tag) => `<button type="button" data-tag="${safe(tag)}" class="${filters.tag === tag ? "selected" : ""}">${tag === "all" ? "Everything" : safe(tag)}</button>`).join("")}</div>
    <p class="result-count" aria-live="polite">Showing ${shown.length} of ${products.length} pieces</p>
    <div class="product-grid">${shown.length ? shown.map(productCard).join("") : `<div class="no-results"><h3>Nothing by that name just yet.</h3><button class="text-link" data-reset>Reset filters</button></div>`}</div>
  </section>
  <section class="custom-callout"><div><div class="eyebrow">Made personal</div><h2>A little more <em>you.</em></h2><p>Choose a colour and fragrance for a candle that feels like it was always yours.</p></div><a class="button button-light" href="https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent("Hi Rama Aurantha, I would like to discuss a custom candle.")}" target="_blank" rel="noopener">Ask about custom orders <span aria-hidden="true">↗</span></a></section>`;
}

function renderProduct(id) {
  const product = productById(id);
  if (!product) return renderNotFound();
  app.innerHTML = `<section class="detail-layout">
    <a class="back-link" href="#/shop">← Back to collection</a>
    <div class="detail-image"><img src="${product.image}" alt="${safe(product.name)}" onerror="this.src='https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=960&q=80'"></div>
    <div class="detail-copy"><div class="eyebrow">${product.code} · ${product.category === "urli" ? "Urli candle" : "Soy wax candle"}</div><h1>${safe(product.name)}</h1><div class="detail-price">${money(product.price)}</div><p>${Number(product.stockQuantity) > 0 ? `${Number(product.stockQuantity)} available` : "Currently out of stock"}. Hand-finished in small batches, with considered details and a soft, warm glow.</p><div class="tag-list">${product.tags.map((tag) => `<span class="tag">${safe(tag)}</span>`).join("")}</div><button class="button" data-add="${product.id}" ${Number(product.stockQuantity) < 1 ? "disabled" : ""}>${Number(product.stockQuantity) > 0 ? "Add to bag" : "Out of stock"} <span aria-hidden="true">+</span></button><p class="detail-note">Complimentary delivery on orders over ₹1,499. Made to order; dispatch timing is confirmed after purchase.</p><a class="text-link" href="#/cart">View your bag</a></div>
  </section>`;
}

function renderCart() {
  const items = cartDetails();
  const subtotalAmount = subtotal();
  app.innerHTML = `<section class="page-shell"><div class="eyebrow">Your selection</div><h1 class="page-title">Shopping bag</h1>${items.length ? `<div class="cart-layout"><div class="cart-lines">${items.map(({ product, quantity }) => `<article class="cart-line"><a class="cart-thumb" href="#/product/${product.id}"><img src="${product.image}" alt="${safe(product.name)}"></a><div class="cart-line-copy"><span class="eyebrow">${product.code}</span><h2><a href="#/product/${product.id}">${safe(product.name)}</a></h2><div class="line-price">${money(product.price)} · ${Number(product.stockQuantity) || 0} in stock</div><button type="button" class="remove-link" data-remove="${product.id}">Remove</button></div><div class="quantity-control" aria-label="Quantity for ${safe(product.name)}"><button type="button" data-quantity="${product.id}" data-change="-1" aria-label="Decrease quantity">−</button><span>${quantity}</span><button type="button" data-quantity="${product.id}" data-change="1" aria-label="Increase quantity" ${quantity >= Number(product.stockQuantity) ? "disabled" : ""}>+</button></div><strong class="line-total">${money(product.price * quantity)}</strong></article>`).join("")}</div><aside class="order-summary"><h2>Order summary</h2><div class="summary-row"><span>Subtotal</span><span>${money(subtotalAmount)}</span></div><div class="summary-row"><span>Delivery</span><span>${shipping() ? money(shipping()) : "Complimentary"}</span></div><p class="shipping-hint">${subtotalAmount >= 1499 ? "Your delivery is on us." : `Add ${money(1499 - subtotalAmount)} for complimentary delivery.`}</p><div class="summary-row summary-total"><span>Total</span><strong>${money(subtotalAmount + shipping())}</strong></div><a class="button full-width" href="#/checkout">Continue to checkout <span aria-hidden="true">→</span></a><a class="continue-link" href="#/shop">Continue shopping</a></aside></div>` : `<div class="empty-state"><div class="empty-mark">RA</div><h2>Your bag is waiting for its first candle.</h2><p>Explore the collection and find one that feels like home.</p><a class="button" href="#/shop">Explore the collection</a></div>`}</section>`;
}

function renderAccount() {
  if (!profile) {
    app.innerHTML = `<section class="account-shell page-shell"><div class="account-intro"><div class="eyebrow">Your Rama Aurantha</div><h1 class="page-title">Welcome back.</h1><p>Save your contact details and delivery addresses for a smoother checkout.</p></div><form id="login-form" class="account-form"><h2>Sign in or create your profile</h2><label class="field"><span>Your name</span><input name="name" autocomplete="name" required placeholder="Name"></label><label class="field"><span>Email address</span><input name="email" type="email" autocomplete="email" required placeholder="you@example.com"></label><label class="field"><span>Mobile number</span><input name="phone" type="tel" autocomplete="tel" inputmode="tel" pattern="[0-9+][0-9+ ]{7,17}" placeholder="+91 98765 43210"></label><button class="button full-width" type="submit">Continue to account <span aria-hidden="true">→</span></button><p class="account-note">This demo saves your profile on this device only. Verified sign-in and OTP require a connected authentication service.</p></form></section>`;
    return;
  }

  app.innerHTML = `<section class="page-shell account-shell"><div class="account-heading"><div><div class="eyebrow">Your Rama Aurantha</div><h1 class="page-title">Hello, ${safe(profile.name.split(" ")[0])}.</h1><p>${safe(profile.email)}${profile.phone ? ` · ${safe(profile.phone)}` : ""}</p></div><button type="button" class="remove-link account-signout" data-signout>Sign out</button></div><div class="address-book"><div class="address-book-heading"><div><div class="eyebrow">Saved for next time</div><h2>Your addresses</h2></div><span>${addresses.length} saved</span></div>${addresses.length ? `<div class="saved-addresses">${addresses.map((address, index) => `<article class="saved-address"><span class="address-number">${String(index + 1).padStart(2, "0")}</span><div><strong>${safe(address.name)}</strong><p>${safe(address.address)}${address.landmark ? `, ${safe(address.landmark)}` : ""}<br>${safe(address.city)}, ${safe(address.state)} ${safe(address.pincode)}<br>${safe(address.phone)}</p></div><button type="button" class="remove-link" data-delete-address="${index}">Remove</button></article>`).join("")}</div>` : `<p class="empty-address">No saved addresses yet. Add one below or save it during checkout.</p>`}<form id="address-form" class="address-form"><h3>Add a delivery address</h3><div class="form-grid"><label class="field full"><span>Full name</span><input name="name" autocomplete="name" required value="${safe(profile.name)}"></label><label class="field full"><span>Street address</span><input name="address" autocomplete="street-address" required placeholder="House / flat, street and area"></label><label class="field"><span>City</span><input name="city" autocomplete="address-level2" required></label><label class="field"><span>State</span><input name="state" autocomplete="address-level1" required></label><label class="field"><span>PIN code</span><input name="pincode" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{6}" required placeholder="6 digits"></label><label class="field"><span>Landmark <small>Optional</small></span><input name="landmark" placeholder="Nearby landmark"></label><label class="field full"><span>Mobile number</span><input name="phone" type="tel" autocomplete="tel" inputmode="tel" pattern="[0-9+][0-9+ ]{7,17}" required value="${safe(profile.phone || "")}" placeholder="+91 98765 43210"></label></div><button class="button" type="submit">Save address <span aria-hidden="true">+</span></button></form></div><a class="text-link account-shop-link" href="#/shop">Continue shopping <span aria-hidden="true">→</span></a></section>`;
}

function renderCheckout() {
  if (!cartDetails().length) return renderCart();
  const savedAddress = addresses[addresses.length - 1] || {};
  const customerName = profile?.name || savedAddress.name || "";
  const customerEmail = profile?.email || "";
  const customerPhone = profile?.phone || savedAddress.phone || "";
  app.innerHTML = `<section class="page-shell checkout-shell"><div class="eyebrow">Almost yours</div><h1 class="page-title">Checkout</h1><div class="checkout-layout"><form id="checkout-form" class="checkout-form">
    <fieldset><legend><span>01</span> Contact</legend><div class="form-grid"><label class="field full"><span>Email address</span><input name="email" type="email" autocomplete="email" required value="${safe(customerEmail)}" placeholder="you@example.com"></label><label class="field full"><span>Mobile number</span><input name="phone" type="tel" autocomplete="tel" inputmode="tel" pattern="[0-9+][0-9+ ]{7,17}" required value="${safe(customerPhone)}" placeholder="+91 98765 43210"></label></div></fieldset>
    <fieldset><legend><span>02</span> Delivery address</legend><div class="form-grid"><label class="field full"><span>Full name</span><input name="name" autocomplete="name" required value="${safe(customerName)}" placeholder="Name for delivery"></label><label class="field full"><span>Address</span><input name="address" autocomplete="street-address" required value="${safe(savedAddress.address || "")}" placeholder="House / flat, street and area"></label><label class="field"><span>City</span><input name="city" autocomplete="address-level2" required value="${safe(savedAddress.city || "")}"></label><label class="field"><span>State</span><input name="state" autocomplete="address-level1" required value="${safe(savedAddress.state || "")}"></label><label class="field"><span>PIN code</span><input name="pincode" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{6}" required value="${safe(savedAddress.pincode || "")}" placeholder="6 digits"></label><label class="field"><span>Landmark <small>Optional</small></span><input name="landmark" value="${safe(savedAddress.landmark || "")}" placeholder="Nearby landmark"></label><label class="field full"><span>Order note <small>Optional</small></span><textarea name="note" rows="3" placeholder="Fragrance preference, gift note or delivery detail"></textarea></label><label class="save-address-option field full"><span><input type="checkbox" name="saveAddress" checked> Save this address on this device</span></label></div></fieldset>
    <fieldset><legend><span>03</span> Payment</legend><div class="payment-options"><label class="payment-option"><input type="radio" name="payment" value="upi" checked><span class="payment-mark">UPI</span><span><strong>UPI via YouPI</strong><small>Continue to the self-hosted YouPI checkout to pay by UPI.</small></span></label><label class="payment-option"><input type="radio" name="payment" value="cod"><span class="payment-mark">COD</span><span><strong>Cash on delivery</strong><small>Pay when your candles arrive. Availability is confirmed by our team.</small></span></label></div><p class="payment-disclaimer">YouPI is experimental. UPI payments remain pending until a matching bank-statement reconciliation; an alert or browser message alone is not payment confirmation.</p></fieldset>
    <button class="button full-width submit-order" type="submit">Continue to secure UPI checkout <span aria-hidden="true">→</span></button><p id="checkout-feedback" class="checkout-feedback" role="alert" hidden></p><p class="form-footnote">By placing this request, you agree that Rama Aurantha may contact you to confirm the order.</p>
  </form><aside class="order-summary checkout-summary"><h2>In your order <span>${cart.reduce((sum, item) => sum + item.quantity, 0)}</span></h2>${cartDetails()
    .map(
      ({ product, quantity }) =>
        `<div class="mini-line"><img src="${product.image}" alt=""><div><strong>${safe(product.name)}</strong><small>Qty ${quantity}</small></div><span>${money(product.price * quantity)}</span></div>`,
    )
    .join(
      "",
    )}<div class="summary-row"><span>Subtotal</span><span>${money(subtotal())}</span></div><div class="summary-row"><span>Delivery</span><span>${shipping() ? money(shipping()) : "Complimentary"}</span></div><div class="summary-row summary-total"><span>Total</span><strong>${money(subtotal() + shipping())}</strong></div><a class="continue-link" href="#/cart">Edit bag</a></aside></div></section>`;
}

function renderConfirmation() {
  const order = readStored(ORDER_KEY, null);
  if (!order) return renderNotFound();
  const paymentStatus = order.payment === "upi"
    ? order.statusToken ? order.paymentStatus || "PENDING" : "NOT_CONNECTED"
    : "";
  const paymentStatusText = {
    NOT_CONNECTED: "This older order is not linked to YouPI. Contact the store before transferring money.",
    PENDING: "Payment is pending. Complete the UPI checkout and refresh this status.",
    AWAITING_RECONCILIATION: "Payment was observed, but is not yet confirmed. Wait for bank-statement reconciliation.",
    MANUAL_REVIEW: "Payment needs manual review. Contact the store before trying again.",
    PAID: "Payment has been reconciled against a bank statement.",
    FAILED: "The payment was not completed. You can contact the store before retrying.",
    EXPIRED: "The payment session expired. Contact the store if you already transferred money.",
    CANCELLED: "The payment was cancelled. No payment is confirmed.",
    UNVERIFIED_SUCCESS: "The gateway reported success, but settlement is not verified. Contact the store.",
    CREATE_FAILED: "The gateway could not start this payment. Your order was not submitted.",
  }[paymentStatus] || "Payment status is unavailable. Contact the store before taking further action.";
  const message = [
    "Hi Rama Aurantha, I have placed an order request.",
    `Order: ${order.number}`,
    ...order.items.map(
      (item) =>
        `${item.name} x ${item.quantity} - ${money(item.price * item.quantity)}`,
    ),
    `Total: ${money(order.total)}`,
    `Payment preference: ${order.payment === "upi" ? `YouPI UPI (${paymentStatus})` : "Cash on delivery"}`,
    `Deliver to: ${order.customer.name}, ${order.customer.address}, ${order.customer.city}, ${order.customer.state} ${order.customer.pincode}`,
    `Mobile: ${order.customer.phone}`,
    `Email: ${order.customer.email}`,
    order.customer.note ? `Note: ${order.customer.note}` : "",
  ]
    .filter(Boolean)
    .join("\n");
  app.innerHTML = `<section class="confirmation-page"><div class="confirmation-stamp" aria-hidden="true">RA</div><div class="eyebrow">Order request received</div><h1>Thank you, ${safe(order.customer.name.split(" ")[0])}.</h1><p>${order.payment === "upi" ? "Your order request is saved. Payment is not considered complete until the gateway reports a reconciled UPI transaction." : "We have saved your order request. Send the details to our team on WhatsApp so we can confirm availability and delivery timing."}</p><div class="confirmation-number"><span>Order reference</span><strong>${safe(order.number)}</strong></div><div class="confirmation-total"><span>Order total</span><strong>${money(order.total)}</strong></div><p class="confirmation-payment">${order.payment === "upi" ? safe(paymentStatusText) : "Cash on delivery selected. Our team will confirm COD availability for your address."}</p>${order.payment === "upi" && order.statusToken ? `<button class="button" type="button" data-check-payment>Refresh payment status</button><p id="payment-status-feedback" class="checkout-feedback" role="alert" hidden></p>` : ""}<a class="button" href="https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}" target="_blank" rel="noopener">Contact us on WhatsApp <span aria-hidden="true">↗</span></a><a class="text-link" href="#/shop">Back to the collection</a></section>`;
}

function renderNotFound() {
  app.innerHTML = `<section class="empty-state"><div class="eyebrow">Page not found</div><h1>That page has moved.</h1><a class="button" href="#/shop">Return to the collection</a></section>`;
}

function renderAdmin(editingId = "") {
  if (!adminAuthenticated) {
    app.innerHTML = `<section class="page-shell admin-shell"><div class="admin-heading"><div><div class="eyebrow">Studio catalogue</div><h1 class="page-title">Admin sign in</h1><p>Sign in to manage products for the storefront.</p></div><a class="text-link" href="#/shop">View storefront <span aria-hidden="true">↗</span></a></div><form id="admin-login-form" class="admin-form admin-login-form"><div class="admin-form-heading"><div class="eyebrow">Administrator access</div><h2>Welcome back</h2></div><label class="field"><span>User ID</span><input name="username" autocomplete="username" required></label><label class="field"><span>Password</span><input name="password" type="password" autocomplete="current-password" required></label><button class="button" type="submit">Sign in <span aria-hidden="true">→</span></button><p class="admin-feedback" id="admin-login-feedback" role="status" aria-live="polite">${safe(backendError)}</p></form></section>`;
    return;
  }
  const editing = products.find((product) => product.id === editingId);
  const values = editing || {
    code: "",
    name: "",
    category: "urli",
    price: "",
    stockQuantity: 0,
    tags: [],
    image: "",
  };
  app.innerHTML = `<section class="page-shell admin-shell">
    <div class="admin-heading"><div><div class="eyebrow">Studio catalogue</div><h1 class="page-title">Product admin</h1><p>Signed in as ${safe(adminUsername)} (${adminRole === "owner" ? "Owner admin" : "Co-admin"}). Changes are saved to the shared store catalogue.</p></div><div class="admin-heading-actions"><a class="text-link" href="#/shop">View storefront <span aria-hidden="true">↗</span></a><button type="button" class="remove-link" data-admin-logout>Sign out</button></div></div>
    <div class="admin-notice" role="note"><strong>Connected to the shared catalogue.</strong> Product listings are stored in the rama database and visible to every storefront visitor.</div>
    <div class="admin-layout">
      <form id="product-form" class="admin-form" data-editing-id="${safe(editingId)}">
        <div class="admin-form-heading"><div class="eyebrow">${editing ? "Edit listing" : "New listing"}</div><h2>${editing ? "Update product" : "Add a product"}</h2></div>
        <label class="field"><span>Product code</span><input name="code" required maxlength="24" value="${safe(values.code)}" placeholder="U-15"></label>
        <label class="field"><span>Product name</span><input name="name" required maxlength="90" value="${safe(values.name)}" placeholder="Lotus Pond Urli"></label>
        <div class="form-grid"><label class="field"><span>Collection</span><select name="category"><option value="urli" ${values.category === "urli" ? "selected" : ""}>Urli candle</option><option value="soy" ${values.category === "soy" ? "selected" : ""}>Soy wax</option></select></label><label class="field"><span>Price (₹)</span><input name="price" type="number" min="1" step="1" required value="${safe(values.price)}" placeholder="1299"></label></div>
        <label class="field"><span>Available inventory (units)</span><input name="stockQuantity" type="number" min="0" max="1000000" step="1" required value="${safe(values.stockQuantity ?? 0)}" placeholder="0"><small>Only this many units can be ordered. Reserved orders reduce available inventory.</small></label>
        <label class="field"><span>Tags <small>Separate with commas</small></span><input name="tags" maxlength="120" value="${safe(values.tags.join(", "))}" placeholder="Floral, Gift-ready"></label>
        <div class="admin-image-preview" id="admin-image-preview" ${values.image ? "" : "hidden"}><img src="${safe(values.image)}" alt="Current product image"></div>
        <label class="field"><span>Upload product image <small>PNG, JPEG, or WebP; stored on the server</small></span><input name="imageFile" type="file" accept="image/png,image/jpeg,image/webp"></label>
        <label class="field"><span>Or use an image URL</span><input name="imageUrl" type="url" placeholder="https://example.com/product.jpg" ${editing?.image.startsWith("data:") ? "" : `value="${safe(editing?.image || "")}"`}></label>
        <div class="admin-form-actions"><button class="button" type="submit">${editing ? "Save changes" : "Add listing"} <span aria-hidden="true">→</span></button>${editing ? `<button class="text-link" type="button" data-cancel-edit>Cancel edit</button>` : ""}</div>
        <p class="admin-feedback" id="admin-feedback" role="status" aria-live="polite"></p>
      </form>
      <section class="admin-listing-section" aria-labelledby="admin-listings-title"><div class="admin-listing-heading"><div><div class="eyebrow">${products.length} listings</div><h2 id="admin-listings-title">Your products</h2></div><a class="text-link" href="#/shop">Open shop <span aria-hidden="true">↗</span></a></div><div class="admin-listings">${products.map((product) => `<article class="admin-listing"><img src="${safe(product.image)}" alt="" loading="lazy"><div class="admin-listing-copy"><span>${safe(product.code)} · ${product.category === "urli" ? "Urli" : "Soy wax"}</span><h3>${safe(product.name)}</h3><strong>${money(product.price)}</strong><small class="stock-label">${Number(product.stockQuantity) || 0} in stock</small></div><div class="admin-listing-actions"><button type="button" class="admin-action" data-edit-product="${safe(product.id)}" aria-label="Edit ${safe(product.name)}" title="Edit listing">Edit</button>${adminRole === "owner" ? `<button type="button" class="admin-action admin-delete" data-delete-product="${safe(product.id)}" aria-label="Delete ${safe(product.name)}" title="Delete listing">Delete</button>` : ""}</div></article>`).join("")}</div></section>
    </div>
    ${adminRole === "owner" ? `<form id="coadmin-form" class="admin-form coadmin-form"><div class="admin-form-heading"><div class="eyebrow">Team access</div><h2>Add a co-admin</h2></div><p>Create a separate login so a trusted helper can add or edit products. Co-admins cannot delete products or create other accounts.</p><div class="form-grid"><label class="field"><span>Co-admin user ID</span><input name="username" required minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" autocomplete="off" placeholder="e.g. helper01"></label><label class="field"><span>Temporary password</span><input name="password" type="password" required minlength="12" maxlength="200" autocomplete="new-password" placeholder="At least 12 characters"></label></div><div class="admin-form-actions"><button class="button" type="submit">Create co-admin</button></div><p class="admin-feedback" id="coadmin-feedback" role="status" aria-live="polite"></p></form>` : ""}
  </section>`;
}

function resizeProductImage(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onerror = () => reject(new Error("The image could not be read."));
    reader.onload = () => {
      const image = new Image();
      image.onerror = () => reject(new Error("Choose a valid image file."));
      image.onload = () => {
        const scale = Math.min(1, 1200 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement("canvas");
        canvas.width = Math.round(image.naturalWidth * scale);
        canvas.height = Math.round(image.naturalHeight * scale);
        canvas.getContext("2d").drawImage(image, 0, 0, canvas.width, canvas.height);
        const result = canvas.toDataURL("image/webp", 0.78);
        if (result.length > 900000) reject(new Error("This image is still too large. Try a smaller image."));
        else resolve(result);
      };
      image.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
}

function renderRoute() {
  const [page, id] = routeParts();
  const currentRoute = `#/${routeParts().join("/")}`;
  document.querySelectorAll("[data-nav]").forEach((link) => {
    link.classList.toggle("active", link.getAttribute("href") === currentRoute);
  });
  const accountLink = document.querySelector("[data-account-link]");
  if (accountLink) {
    accountLink.href = profile ? "#/account" : "#/login";
    accountLink.textContent = profile ? "Account" : "Sign in";
  }
  if (page === "shop") renderShop();
  else if (page === "product") renderProduct(id);
  else if (page === "cart") renderCart();
  else if (page === "checkout") renderCheckout();
  else if (page === "confirmation") renderConfirmation();
  else if (page === "login" || page === "account") renderAccount();
  else if (page === "admin") renderAdmin();
  else renderNotFound();
  updateCartCount();
  if (location.hash.includes("collection"))
    document.querySelector("#collection")?.scrollIntoView();
  else window.scrollTo({ top: 0, behavior: "smooth" });
}

app.addEventListener("click", (event) => {
  const checkPaymentButton = event.target.closest("[data-check-payment]");
  if (checkPaymentButton) {
    const order = readStored(ORDER_KEY, null);
    if (!order?.statusToken) return;
    const feedback = document.querySelector("#payment-status-feedback");
    checkPaymentButton.disabled = true;
    apiRequest("payments", "status", {
      method: "POST",
      body: { orderId: order.number, token: order.statusToken },
    }).then((result) => {
      order.paymentStatus = result.status;
      localStorage.setItem(ORDER_KEY, JSON.stringify(order));
      renderConfirmation();
    }).catch((error) => {
      feedback.textContent = error.message;
      feedback.hidden = false;
    }).finally(() => {
      checkPaymentButton.disabled = false;
    });
    return;
  }
  if (event.target.closest("[data-admin-logout]")) {
    apiRequest("admin", "logout", { method: "POST", authenticated: true })
      .catch(() => {})
      .finally(() => {
        adminAuthenticated = false;
        adminRole = "";
        adminUsername = "";
        csrfToken = "";
        renderAdmin();
      });
    return;
  }
  const editButton = event.target.closest("[data-edit-product]");
  if (editButton) {
    renderAdmin(editButton.dataset.editProduct);
    window.scrollTo({ top: 0, behavior: "smooth" });
    return;
  }
  const deleteButton = event.target.closest("[data-delete-product]");
  if (deleteButton) {
    const product = productById(deleteButton.dataset.deleteProduct);
    if (!product || !window.confirm(`Delete “${product.name}” from the catalogue?`)) return;
    apiRequest("products", "delete", {
      method: "POST",
      authenticated: true,
      body: { id: product.id },
    }).then(() => {
      products = products.filter((item) => item.id !== product.id);
      cart = cart.filter((item) => item.id !== product.id);
      cacheProducts();
      persistCart();
      renderAdmin();
    }).catch((error) => {
      renderAdmin();
      document.querySelector("#admin-feedback").textContent = error.message;
    });
    return;
  }
  if (event.target.closest("[data-cancel-edit]")) {
    renderAdmin();
    return;
  }
  if (event.target.closest("[data-signout]")) {
    profile = null;
    addresses = [];
    localStorage.removeItem(PROFILE_KEY);
    localStorage.removeItem(ADDRESS_KEY);
    renderRoute();
    navigate("login");
    return;
  }
  const deleteAddressButton = event.target.closest("[data-delete-address]");
  if (deleteAddressButton) {
    addresses.splice(Number(deleteAddressButton.dataset.deleteAddress), 1);
    saveAddresses();
    renderAccount();
    return;
  }
  const addButton = event.target.closest("[data-add]");
  if (addButton) {
    addToCart(addButton.dataset.add);
    return;
  }
  const categoryButton = event.target.closest("[data-category]");
  if (categoryButton) {
    filters.category = categoryButton.dataset.category;
    renderShop();
    return;
  }
  const tagButton = event.target.closest("[data-tag]");
  if (tagButton) {
    filters.tag = tagButton.dataset.tag;
    renderShop();
    return;
  }
  const quantityButton = event.target.closest("[data-quantity]");
  if (quantityButton) {
    const item = cart.find(
      (entry) => entry.id === quantityButton.dataset.quantity,
    );
    if (item) {
      const product = productById(item.id);
      const nextQuantity = item.quantity + Number(quantityButton.dataset.change);
      if (product && nextQuantity > Number(product.stockQuantity)) {
        const toast = document.querySelector("#cart-toast");
        toast.textContent = `Only ${Number(product.stockQuantity)} unit(s) of ${product.name} are in stock`;
        toast.classList.add("visible");
        window.clearTimeout(addToCart.toastTimer);
        addToCart.toastTimer = window.setTimeout(() => toast.classList.remove("visible"), 2200);
      } else {
        item.quantity = nextQuantity;
      }
    }
    cart = cart.filter((entry) => entry.quantity > 0);
    persistCart();
    renderCart();
    return;
  }
  const removeButton = event.target.closest("[data-remove]");
  if (removeButton) {
    cart = cart.filter((entry) => entry.id !== removeButton.dataset.remove);
    persistCart();
    renderCart();
    return;
  }
  if (event.target.closest("[data-reset]")) {
    filters = { category: "all", search: "", sort: "featured", tag: "all" };
    renderShop();
  }
});

app.addEventListener("change", (event) => {
  if (event.target.name !== "imageFile") return;
  const file = event.target.files?.[0];
  if (!file) return;
  const preview = document.querySelector("#admin-image-preview");
  preview.hidden = false;
  preview.querySelector("img").src = URL.createObjectURL(file);
});

app.addEventListener("input", (event) => {
  if (event.target.id === "product-search") {
    filters.search = event.target.value;
    const cursor = event.target.selectionStart;
    renderShop();
    const input = document.querySelector("#product-search");
    input.focus();
    input.setSelectionRange(cursor, cursor);
  }
});

app.addEventListener("change", (event) => {
  if (event.target.id === "product-sort") {
    filters.sort = event.target.value;
    renderShop();
  }
});

function completeCheckout(result, payment, customer, saveAddress) {
  const order = {
    number: result.orderId,
    createdAt: new Date().toISOString(),
    items: result.items,
    subtotal: result.subtotalPaise / 100,
    delivery: result.deliveryPaise / 100,
    total: result.amountPaise / 100,
    payment,
    paymentStatus: payment === "upi" ? "PENDING" : result.status,
    ...(result.statusToken ? { statusToken: result.statusToken } : {}),
    customer,
  };
  profile = {
    name: customer.name,
    email: customer.email,
    phone: customer.phone,
  };
  saveProfile();
  if (saveAddress) {
    addAddress({
      name: customer.name,
      address: customer.address,
      city: customer.city,
      state: customer.state,
      pincode: customer.pincode,
      landmark: customer.landmark,
      phone: customer.phone,
    });
  }
  localStorage.setItem(ORDER_KEY, JSON.stringify(order));
  cart = [];
  persistCart();
  return order;
}

app.addEventListener("submit", (event) => {
  if (event.target.id === "admin-login-form") {
    event.preventDefault();
    const feedback = document.querySelector("#admin-login-feedback");
    const username = String(new FormData(event.target).get("username") || "").trim();
    const password = String(new FormData(event.target).get("password") || "");
    apiRequest("admin", "login", { method: "POST", body: { username, password } })
      .then(async (result) => {
        adminAuthenticated = true;
        adminRole = result.role;
        adminUsername = result.username;
        csrfToken = result.csrf;
        backendError = "";
        await loadAdminCatalog();
        renderAdmin();
      })
      .catch((error) => {
        feedback.textContent = error.message;
      });
    return;
  }

  if (event.target.id === "coadmin-form") {
    event.preventDefault();
    const form = event.target;
    const values = new FormData(form);
    const feedback = document.querySelector("#coadmin-feedback");
    apiRequest("admin", "create-user", {
      method: "POST",
      authenticated: true,
      body: {
        username: String(values.get("username") || "").trim(),
        password: String(values.get("password") || ""),
      },
    }).then((result) => {
      form.reset();
      feedback.textContent = `Co-admin "${result.username}" created. Share the user ID and password with them privately.`;
    }).catch((error) => {
      feedback.textContent = error.message;
    });
    return;
  }

  if (event.target.id === "product-form") {
    event.preventDefault();
    const formElement = event.target;
    const form = new FormData(formElement);
    const editingId = formElement.dataset.editingId;
    const existing = products.find((product) => product.id === editingId);
    const code = String(form.get("code")).trim();
    const duplicateCode = products.some((product) => product.code.toLowerCase() === code.toLowerCase() && product.id !== editingId);
    const feedback = document.querySelector("#admin-feedback");
    if (duplicateCode) {
      feedback.textContent = "That product code is already in use.";
      return;
    }
    const saveListing = async (image) => {
      const name = String(form.get("name")).trim();
      const id = existing?.id || `${code.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "")}-${Date.now().toString(36)}`;
      const product = {
        id,
        code,
        name,
        category: form.get("category"),
        price: Number(form.get("price")),
        stockQuantity: Number(form.get("stockQuantity")),
        tags: String(form.get("tags") || "").split(",").map((tag) => tag.trim()).filter(Boolean),
        image,
      };
      if (!product.image || !Number.isFinite(product.price) || product.price < 1
        || !Number.isInteger(product.stockQuantity) || product.stockQuantity < 0
        || product.stockQuantity > 1000000) {
        feedback.textContent = "Add a valid product image, price, and whole-number inventory quantity.";
        return;
      }
      try {
        const result = await apiRequest("products", "save", {
          method: "POST",
          authenticated: true,
          body: product,
        });
        products = existing
          ? products.map((item) => item.id === existing.id ? result.product : item)
          : [...products, result.product];
        cacheProducts();
        renderAdmin();
      } catch (error) {
        feedback.textContent = error.message;
      }
    };
    const file = form.get("imageFile");
    const imageUrl = String(form.get("imageUrl") || "").trim();
    if (file instanceof File && file.size) {
      resizeProductImage(file).then(saveListing).catch((error) => {
        feedback.textContent = error.message;
      });
    } else {
      const image = imageUrl || existing?.image || "";
      if (image && !image.startsWith("data:image/") && !/^https:\/\//i.test(image)) {
        feedback.textContent = "Use an https image URL or upload an image file.";
        return;
      }
      saveListing(image);
    }
    return;
  }

  if (event.target.id === "login-form") {
    event.preventDefault();
    if (!event.target.reportValidity()) return;
    const form = new FormData(event.target);
    profile = {
      name: String(form.get("name")).trim(),
      email: String(form.get("email")).trim(),
      phone: String(form.get("phone") || "").trim(),
    };
    saveProfile();
    navigate("account");
    return;
  }

  if (event.target.id === "address-form") {
    event.preventDefault();
    if (!event.target.reportValidity()) return;
    const form = new FormData(event.target);
    addAddress(Object.fromEntries(
      ["name", "address", "city", "state", "pincode", "landmark", "phone"].map(
        (key) => [key, String(form.get(key) || "").trim()],
      ),
    ));
    renderAccount();
    return;
  }

  if (event.target.id !== "checkout-form") return;
  event.preventDefault();
  if (!event.target.reportValidity()) return;
  const form = new FormData(event.target);
  const payment = form.get("payment");
  const checkoutForm = event.target;
  const submitButton = checkoutForm.querySelector(".submit-order");
  const feedback = checkoutForm.querySelector("#checkout-feedback");
  const customer = Object.fromEntries(
    [
      "name",
      "email",
      "phone",
      "address",
      "city",
      "state",
      "pincode",
      "landmark",
      "note",
    ].map((key) => [key, String(form.get(key) || "").trim()]),
  );
  submitButton.disabled = true;
  submitButton.textContent = payment === "upi" ? "Connecting to secure checkout…" : "Checking inventory…";
  feedback.hidden = true;
  const isUpi = payment === "upi";
  apiRequest(isUpi ? "payments" : "orders", isUpi ? "create" : "cod", {
    method: "POST",
    body: { items: cart.map(({ id, quantity }) => ({ id, quantity })) },
  }).then((result) => {
    completeCheckout(result, payment, customer, Boolean(form.get("saveAddress")));
    if (isUpi) window.location.assign(result.checkoutUrl);
    else navigate("confirmation");
  }).catch((error) => {
    feedback.textContent = error.message;
    feedback.hidden = false;
    submitButton.disabled = false;
    submitButton.textContent = isUpi ? "Continue to secure UPI checkout →" : "Place order request →";
  });
});

app.addEventListener("change", (event) => {
  if (event.target.name !== "payment") return;
  const submitButton = document.querySelector(".submit-order");
  if (submitButton) {
    submitButton.textContent = event.target.value === "upi"
      ? "Continue to secure UPI checkout →"
      : "Place order request →";
  }
});

window.addEventListener("hashchange", renderRoute);

if (!location.hash) location.hash = "/shop";
else renderRoute();
updateCartCount();
initializeBackend();
