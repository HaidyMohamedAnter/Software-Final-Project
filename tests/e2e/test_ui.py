"""
E2E + UI Tests for Software-Final-Project (XAMPP)
Run: pytest tests/e2e/test_ui.py -v --headed
Requires: pip install playwright pytest-playwright
          playwright install chromium
Base URL: http://localhost/Software-Final-Project
"""

import os
import pytest
from playwright.sync_api import Page, expect

BASE_URL = "http://localhost/Software-Final-Project"

# ─────────────────────────────────────────────
# Login Helpers
# ─────────────────────────────────────────────

USER_EMAIL  = os.getenv("TEST_USER_EMAIL",  "rodydoaa2004@gmail.com")
USER_PASS   = os.getenv("TEST_USER_PASS",   "123")
ADMIN_EMAIL = os.getenv("TEST_ADMIN_EMAIL", "khalil@gmail.com")
ADMIN_PASS  = os.getenv("TEST_ADMIN_PASS",  "123")


def login_as_user(page: Page):
    page.goto(f"{BASE_URL}/login.php")
    page.fill("input[name='email']",    USER_EMAIL)
    page.fill("input[name='password']", USER_PASS)
    page.click("input[type='submit']")
    page.wait_for_load_state("networkidle")


def login_as_admin(page: Page):
    page.goto(f"{BASE_URL}/login.php")
    page.fill("input[name='email']",    ADMIN_EMAIL)
    page.fill("input[name='password']", ADMIN_PASS)
    page.click("input[type='submit']")
    page.wait_for_load_state("networkidle")


# ─────────────────────────────────────────────
# 1. PAGE LOAD TESTS
# ─────────────────────────────────────────────

class TestPageLoads:
    """Every page should load without PHP errors."""

    # about.php confirmed to exist — included in list
    PAGES = [
        ("Home",     "/home.php"),
        ("Shop",     "/shop.php"),
        ("Cart",     "/cart.php"),
        ("Checkout", "/checkout.php"),
        ("Contact",  "/contact.php"),
        ("About",    "/about.php"),
        ("Orders",   "/orders.php"),
        ("Search",   "/search_page.php"),
    ]

    @pytest.mark.parametrize("name,path", PAGES)
    def test_page_loads_after_login(self, page: Page, name: str, path: str):
        login_as_user(page)
        response = page.goto(f"{BASE_URL}{path}")
        assert response.status < 400, f"{name} returned HTTP {response.status}"

    @pytest.mark.parametrize("name,path", PAGES)
    def test_page_has_no_php_errors(self, page: Page, name: str, path: str):
        login_as_user(page)
        page.goto(f"{BASE_URL}{path}")
        content = page.content()
        assert "Fatal error"        not in content, f"Fatal PHP error on {name}"
        assert "Undefined variable" not in content, f"Undefined variable on {name}"
        assert "query failed"       not in content, f"DB query failed on {name}"

    def test_login_page_loads(self, page: Page):
        response = page.goto(f"{BASE_URL}/login.php")
        assert response.status < 400

    def test_register_page_loads(self, page: Page):
        response = page.goto(f"{BASE_URL}/register.php")
        assert response.status < 400

    def test_admin_page_loads(self, page: Page):
        login_as_admin(page)
        response = page.goto(f"{BASE_URL}/admin_page.php")
        assert response.status < 400


# ─────────────────────────────────────────────
# 2. REDIRECT TESTS (no login)
# ─────────────────────────────────────────────

class TestRedirects:
    """Protected pages must redirect to login when not logged in."""

    # about.php uses header('location:login.php') without exit() — browser still
    # follows the redirect, so the test expectation (login.php in URL) is valid.
    PROTECTED = [
        "/home.php",
        "/shop.php",
        "/cart.php",
        "/checkout.php",
        "/orders.php",
        "/contact.php",
        "/about.php",
        "/search_page.php",
    ]

    @pytest.mark.parametrize("path", PROTECTED)
    def test_redirects_to_login_when_logged_out(self, page: Page, path: str):
        page.goto(f"{BASE_URL}{path}")
        page.wait_for_load_state("networkidle")
        assert "login.php" in page.url, \
            f"{path} should redirect to login.php but got {page.url}"

    ADMIN_PROTECTED = [
        "/admin_page.php",
        "/admin_products.php",
        "/admin_orders.php",
        "/admin_users.php",
        "/admin_contacts.php",
    ]

    @pytest.mark.parametrize("path", ADMIN_PROTECTED)
    def test_admin_pages_redirect_when_logged_out(self, page: Page, path: str):
        page.goto(f"{BASE_URL}{path}")
        page.wait_for_load_state("networkidle")
        assert "login.php" in page.url, \
            f"{path} should redirect to login.php but got {page.url}"


# ─────────────────────────────────────────────
# 3. NAVIGATION TESTS
# ─────────────────────────────────────────────

class TestNavigation:

    def test_nav_has_links(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        # header.php navbar: home, about, shop, contact, orders
        links = page.locator("header a, nav a, .navbar a")
        assert links.count() > 0, "No nav links found in header"

    def test_nav_shop_link(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        page.click("a[href*='shop']")
        page.wait_for_load_state("networkidle")
        assert "shop" in page.url.lower()

    def test_nav_cart_link(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        page.click("a[href*='cart']")
        page.wait_for_load_state("networkidle")
        assert "cart" in page.url.lower()

    def test_nav_logout_link_exists_when_logged_in(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        # header.php: logout link is inside .user-box
        logout = page.locator("a[href='logout.php']")
        assert logout.count() > 0, "Logout link should exist when logged in"

    def test_user_icon_toggles_dropdown(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        page.click("#user-btn")
        page.wait_for_timeout(500)
        user_box = page.locator(".user-box")
        expect(user_box).to_be_visible()

    def test_user_dropdown_has_logout(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        page.click("#user-btn")
        page.wait_for_timeout(500)
        logout = page.locator(".user-box a[href='logout.php']")
        expect(logout).to_be_visible()

    def test_about_heading_is_correct(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <div class="heading"><h3>about us</h3> ...
        heading = page.locator(".heading h3")
        expect(heading).to_be_visible()
        assert "about" in heading.inner_text().lower()

    def test_about_has_contact_us_button(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <a href="contact.php" class="btn">contact us</a>
        btn = page.locator("a.btn[href='contact.php']")
        expect(btn).to_be_visible()
        assert "contact" in btn.inner_text().lower()


# ─────────────────────────────────────────────
# 4. AUTHENTICATION TESTS
# ─────────────────────────────────────────────

class TestAuthentication:

    def test_login_form_visible(self, page: Page):
        page.goto(f"{BASE_URL}/login.php")
        expect(page.locator("input[name='email']")).to_be_visible()
        expect(page.locator("input[name='password']")).to_be_visible()

    def test_register_form_visible(self, page: Page):
        page.goto(f"{BASE_URL}/register.php")
        expect(page.locator("form")).to_be_visible()

    def test_register_form_has_correct_fields(self, page: Page):
        page.goto(f"{BASE_URL}/register.php")
        # register.php uses: name, email, password, cpassword — NOT 'username'
        expect(page.locator("input[name='name']")).to_be_visible()
        expect(page.locator("input[name='email']")).to_be_visible()
        expect(page.locator("input[name='password']")).to_be_visible()
        expect(page.locator("input[name='cpassword']")).to_be_visible()

    def test_login_with_invalid_credentials_shows_error(self, page: Page):
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='email']",    "wrong@wrong.com")
        page.fill("input[name='password']", "wrongpassword")
        page.click("input[type='submit']")
        page.wait_for_load_state("networkidle")
        # login.php outputs exactly: 'incorrect email or password!'
        assert "incorrect email or password!" in page.content(), \
            "Expected 'incorrect email or password!' message for bad credentials"

    def test_login_empty_fields_blocked(self, page: Page):
        page.goto(f"{BASE_URL}/login.php")
        page.click("input[type='submit']")
        # HTML5 required fields prevent submission; stays on login page
        assert "login" in page.url.lower()

    def test_valid_login_redirects_to_home(self, page: Page):
        login_as_user(page)
        assert "login" not in page.url.lower(), \
            "After valid login, should not still be on login page"

    def test_register_duplicate_email_shows_error(self, page: Page):
        page.goto(f"{BASE_URL}/register.php")
        # register.php field is input[name='name'], NOT input[name='username']
        page.fill("input[name='name']",      "Duplicate User")
        page.fill("input[name='email']",     USER_EMAIL)
        page.fill("input[name='password']",  "123")
        page.fill("input[name='cpassword']", "123")
        page.click("input[type='submit']")
        page.wait_for_load_state("networkidle")
        # register.php outputs exactly: 'user already exist!'
        assert "user already exist!" in page.content(), \
            "Expected 'user already exist!' for duplicate email registration"

    def test_register_password_mismatch_shows_error(self, page: Page):
        page.goto(f"{BASE_URL}/register.php")
        page.fill("input[name='name']",      "Test Mismatch")
        page.fill("input[name='email']",     "mismatch_test@test.com")
        page.fill("input[name='password']",  "abc123")
        page.fill("input[name='cpassword']", "xyz999")
        page.click("input[type='submit']")
        page.wait_for_load_state("networkidle")
        # register.php outputs exactly: 'confirm password not matched!'
        assert "confirm password not matched!" in page.content(), \
            "Expected 'confirm password not matched!' for mismatched passwords"


# ─────────────────────────────────────────────
# 5. SHOP / PRODUCT TESTS
# ─────────────────────────────────────────────

class TestShop:

    def test_products_displayed_on_home(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        # home.php shows max 6 products (LIMIT 6)
        products = page.locator("section.products .box")
        empty    = page.locator("p.empty")
        assert products.count() > 0 or empty.count() > 0, \
            "Home page should show products or empty message"

    def test_product_has_image(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        boxes = page.locator("section.products .box")
        if boxes.count() == 0:
            pytest.skip("No products to test")
        first_img = page.locator("section.products .box img").first
        expect(first_img).to_be_visible()

    def test_product_has_price(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        boxes = page.locator("section.products .box")
        if boxes.count() == 0:
            pytest.skip("No products to test")
        # shop.php: <div class="price">{price} EGP</div>
        price = page.locator("section.products .box .price").first
        expect(price).to_be_visible()
        assert "EGP" in price.inner_text(), "Price should contain EGP"

    def test_product_has_name(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        boxes = page.locator("section.products .box")
        if boxes.count() == 0:
            pytest.skip("No products to test")
        name = page.locator("section.products .box .name").first
        expect(name).to_be_visible()
        assert len(name.inner_text().strip()) > 0, "Product name should not be empty"

    def test_each_product_box_has_button_or_disabled(self, page: Page):
        """
        shop.php shows input[name='add_to_cart'] when stock is available,
        or a disabled <button> when out of stock / limit reached.
        Every product box must have one of these.
        """
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        boxes = page.locator("section.products .box")
        if boxes.count() == 0:
            pytest.skip("No products to test")
        for i in range(boxes.count()):
            box      = boxes.nth(i)
            has_add      = box.locator("input[name='add_to_cart']").count() > 0
            has_disabled = box.locator("button[disabled]").count() > 0
            assert has_add or has_disabled, \
                f"Product box {i} has neither add-to-cart nor disabled button"

    def test_shop_page_has_products_or_empty(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        boxes = page.locator("section.products .box")
        empty = page.locator("p.empty")
        assert boxes.count() > 0 or empty.count() > 0, \
            "Shop page shows neither products nor empty message"

    def test_add_product_to_cart_from_shop(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        btns = page.locator("input[name='add_to_cart']")
        if btns.count() == 0:
            pytest.skip("No add-to-cart buttons available (all out of stock)")
        btns.first.click()
        page.wait_for_load_state("networkidle")
        content = page.content()
        # shop.php outputs exactly one of these two messages via $message[]
        assert "Cart updated successfully!" in content or "Product added to cart!" in content, \
            "No success message after adding to cart from shop"

    def test_search_with_query_returns_results_or_empty(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/search_page.php")
        page.fill("input[name='search']", "a")
        page.click("input[name='submit']")
        page.wait_for_load_state("networkidle")
        # search_page.php shows .box items or 'no result found!'
        boxes = page.locator("section.products .box")
        empty = page.locator("p.empty")
        assert boxes.count() > 0 or empty.count() > 0, \
            "Search returned neither results nor empty message"

    def test_search_no_query_shows_prompt(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/search_page.php")
        # Without POST, search_page.php echoes: 'search something!'
        assert "search something!" in page.content(), \
            "Search page should prompt user to search when no query submitted"

    def test_stock_label_shown_for_every_product(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/shop.php")
        # shop.php always renders one <div class="stock"> per product box
        boxes        = page.locator("section.products .box")
        stock_labels = page.locator("section.products .box .stock")
        if boxes.count() > 0:
            assert stock_labels.count() == boxes.count(), \
                "Every product box should have a stock label"

    def test_home_shows_max_six_products(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/home.php")
        # home.php uses LIMIT 6
        boxes = page.locator("section.products .box")
        assert boxes.count() <= 6, \
            f"Home page should show at most 6 products (LIMIT 6), got {boxes.count()}"


# ─────────────────────────────────────────────
# 6. CART TESTS
# ─────────────────────────────────────────────

class TestCart:

    def test_cart_page_loads(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        assert page.locator("body").is_visible()
        assert "Fatal error"  not in page.content()
        assert "query failed" not in page.content()

    def test_cart_page_has_content(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        # cart.php shows .box items or 'your cart is empty'
        boxes = page.locator(".box-container .box")
        empty = page.locator("p.empty")
        assert boxes.count() > 0 or empty.count() > 0, \
            "Cart page shows neither items nor empty message"

    def test_cart_shows_grand_total(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        # cart.php: <div class="cart-total"> ... EGP
        total = page.locator(".cart-total")
        expect(total).to_be_visible()
        assert "EGP" in total.inner_text(), "Grand total should show EGP"

    def test_cart_has_checkout_link(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        # cart.php: <a href="checkout.php" class="btn ...">proceed to checkout</a>
        checkout = page.locator("a[href='checkout.php']")
        assert checkout.count() > 0, "No checkout link found on cart page"

    def test_cart_has_continue_shopping_link(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        # cart.php: <a href="shop.php" class="option-btn">continue shopping</a>
        continue_btn = page.locator("a[href='shop.php']")
        assert continue_btn.count() > 0, "No 'continue shopping' link found on cart page"

    def test_cart_quantity_update_form_exists(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        boxes = page.locator(".box-container .box")
        if boxes.count() == 0:
            pytest.skip("Cart is empty, cannot test quantity update form")
        # cart.php: input[name='cart_quantity'] and input[name='update_cart'] per item
        expect(page.locator("input[name='cart_quantity']").first).to_be_visible()
        expect(page.locator("input[name='update_cart']").first).to_be_visible()

    def test_cart_item_has_subtotal(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        boxes = page.locator(".box-container .box")
        if boxes.count() == 0:
            pytest.skip("Cart is empty, cannot test subtotal")
        # cart.php: <div class="sub-total"> sub total : <span>... EGP</span>
        subtotal = page.locator(".box .sub-total").first
        expect(subtotal).to_be_visible()
        assert "EGP" in subtotal.inner_text(), "Sub-total should contain EGP"

    def test_cart_max_quantity_respects_warehouse_stock(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/cart.php")
        qty_inputs = page.locator("input[name='cart_quantity']")
        if qty_inputs.count() == 0:
            pytest.skip("Cart is empty")
        # cart.php sets max="warehouse_stock" on the quantity input
        first_max = qty_inputs.first.get_attribute("max")
        assert first_max is not None and int(first_max) > 0, \
            "Cart quantity input should have a positive max attribute (warehouse stock)"


# ─────────────────────────────────────────────
# 7. CHECKOUT TESTS
# ─────────────────────────────────────────────

class TestCheckout:

    def test_checkout_page_loads(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/checkout.php")
        assert page.locator("body").is_visible()
        assert "Fatal error"  not in page.content()
        assert "query failed" not in page.content()

    def test_checkout_form_has_all_required_fields(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/checkout.php")
        # checkout.php form fields: name, number, email, method, flat, street, city, country, pin_code
        for field in ["name", "number", "email", "method", "flat", "street", "city", "country", "pin_code"]:
            locator = page.locator(f"input[name='{field}'], select[name='{field}']")
            assert locator.count() > 0, f"Checkout form missing field: {field}"

    def test_checkout_has_payment_method_dropdown(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/checkout.php")
        # checkout.php: select[name='method'] with cash/credit/paypal options
        method_select = page.locator("select[name='method']")
        expect(method_select).to_be_visible()
        options = method_select.locator("option")
        assert options.count() >= 3, "Payment method dropdown should have at least 3 options"

    def test_checkout_redirects_unauthenticated(self, page: Page):
        page.goto(f"{BASE_URL}/checkout.php")
        page.wait_for_load_state("networkidle")
        assert "login.php" in page.url, \
            "Unauthenticated user should be redirected to login"

    def test_checkout_shows_cart_summary(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/checkout.php")
        # checkout.php: section.display-order shows cart items and grand total
        display = page.locator("section.display-order")
        expect(display).to_be_visible()
        assert "EGP" in display.inner_text() or "empty" in display.inner_text().lower(), \
            "Checkout summary should show EGP total or empty cart message"


# ─────────────────────────────────────────────
# 8. ORDERS TESTS
# ─────────────────────────────────────────────

class TestOrders:

    def test_orders_page_has_heading(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/orders.php")
        # orders.php: <div class="heading"><h3>your orders</h3>
        heading = page.locator(".heading h3")
        expect(heading).to_be_visible()
        assert "orders" in heading.inner_text().lower()

    def test_orders_shows_items_or_empty(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/orders.php")
        # orders.php shows .box items or 'no orders placed yet!'
        boxes = page.locator(".box-container .box")
        empty = page.locator("p.empty")
        assert boxes.count() > 0 or empty.count() > 0, \
            "Orders page shows neither orders nor empty message"

    def test_order_box_has_required_fields(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/orders.php")
        boxes = page.locator(".box-container .box")
        if boxes.count() == 0:
            pytest.skip("No orders to inspect")
        text = boxes.first.inner_text().lower()
        # orders.php outputs: placed on, name, number, email, address,
        # payment method, your orders, total price, payment status
        for field in ["placed on", "name", "email", "address",
                      "payment method", "total price", "payment status"]:
            assert field in text, f"Order box missing field: {field}"

    def test_order_shows_egp_total(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/orders.php")
        boxes = page.locator(".box-container .box")
        if boxes.count() == 0:
            pytest.skip("No orders to inspect")
        assert "EGP" in boxes.first.inner_text(), "Order total should show EGP"

    def test_order_payment_status_has_color(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/orders.php")
        boxes = page.locator(".box-container .box")
        if boxes.count() == 0:
            pytest.skip("No orders to inspect")
        # orders.php applies inline color style to payment status span
        status_span = boxes.first.locator("p span[style*='color']")
        assert status_span.count() > 0, \
            "Payment status should have inline color styling"


# ─────────────────────────────────────────────
# 9. ADMIN PANEL TESTS
# ─────────────────────────────────────────────

class TestAdminPanel:

    def test_admin_dashboard_has_eight_stat_boxes(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_page.php")
        # admin_page.php: exactly 8 .box elements in section.dashboard
        boxes = page.locator("section.dashboard .box")
        assert boxes.count() == 8, \
            f"Dashboard should have 8 stat boxes, found {boxes.count()}"

    def test_admin_dashboard_shows_egp(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_page.php")
        content = page.locator("section.dashboard").inner_text()
        assert "EGP" in content, "Dashboard should show EGP totals"

    def test_admin_dashboard_stat_labels(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_page.php")
        content = page.locator("section.dashboard").inner_text().lower()
        # admin_page.php: exact label strings from the PHP
        for label in ["total pendings", "completed payments", "order placed",
                      "products added", "normal users", "admin users",
                      "total accounts", "new messages"]:
            assert label in content, f"Dashboard missing stat label: '{label}'"

    def test_admin_navbar_has_all_links(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_page.php")
        # admin_header.php navbar: home, products, orders, users, messages
        navbar = page.locator(".navbar")
        text   = navbar.inner_text().lower()
        for link in ["home", "products", "orders", "users", "messages"]:
            assert link in text, f"Admin navbar missing link: '{link}'"

    def test_admin_products_accessible_by_admin(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_products.php")
        content = page.content().lower()
        assert "add product" in content or "product" in content

    def test_admin_blocked_for_regular_user(self, page: Page):
        """
        Admin pages check $_SESSION['admin_id']. A regular user has
        $_SESSION['user_id'] but NOT admin_id, so they get redirected to login.php.
        """
        login_as_user(page)
        page.goto(f"{BASE_URL}/admin_products.php")
        page.wait_for_load_state("networkidle")
        # All admin pages: if(!isset($admin_id)){ header('location:login.php'); exit(); }
        assert "login.php" in page.url, \
            "Regular user should be redirected to login.php when accessing admin pages"

    def test_admin_products_has_add_form(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_products.php")
        # admin_products.php: form with name, price, quantity, image, add_product
        expect(page.locator("input[name='name']")).to_be_visible()
        expect(page.locator("input[name='price']")).to_be_visible()
        expect(page.locator("input[name='quantity']")).to_be_visible()
        expect(page.locator("input[name='image']")).to_be_visible()
        expect(page.locator("input[name='add_product']")).to_be_visible()

    def test_admin_products_has_delete_option(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_products.php")
        # admin_products.php: <a class="delete-btn" href="?delete=...">Delete</a>
        delete_links = page.locator("a.delete-btn")
        # Only meaningful if products exist
        product_boxes = page.locator("section.show-products .box")
        if product_boxes.count() > 0:
            assert delete_links.count() > 0, "Admin products should show delete buttons"

    def test_admin_orders_page_loads(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_orders.php")
        assert "Fatal error"  not in page.content()
        assert "query failed" not in page.content()
        content = page.content().lower()
        assert "order" in content or "placed" in content

    def test_admin_orders_has_status_dropdown(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_orders.php")
        order_boxes = page.locator("section.orders .box")
        if order_boxes.count() == 0:
            pytest.skip("No orders to inspect status dropdown")
        # admin_orders.php: select[name='update_payment'] with pending/completed/rejected
        status_select = page.locator("select[name='update_payment']").first
        expect(status_select).to_be_visible()
        options = [o.inner_text().lower() for o in status_select.locator("option").all()]
        for val in ["pending", "completed", "rejected"]:
            assert any(val in o for o in options), \
                f"Status dropdown missing option: {val}"

    def test_admin_contacts_has_messages_or_empty(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_contacts.php")
        # admin_contacts.php: .box items or 'you have no messages!'
        boxes = page.locator("section.messages .box")
        empty = page.locator("p.empty")
        assert boxes.count() > 0 or empty.count() > 0, \
            "Admin contacts page shows neither messages nor empty notice"

    def test_admin_contacts_message_box_has_fields(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_contacts.php")
        boxes = page.locator("section.messages .box")
        if boxes.count() == 0:
            pytest.skip("No messages to inspect")
        text = boxes.first.inner_text().lower()
        # admin_contacts.php: user id, name, number, email, message
        for field in ["user id", "name", "number", "email", "message"]:
            assert field in text, f"Message box missing field: '{field}'"

    def test_admin_contacts_delete_button_exists(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_contacts.php")
        boxes = page.locator("section.messages .box")
        if boxes.count() == 0:
            pytest.skip("No messages to inspect delete button")
        # admin_contacts.php: <a href="admin_contacts.php?delete=..." class="delete-btn">
        delete_btn = boxes.first.locator("a.delete-btn")
        expect(delete_btn).to_be_visible()
        assert "delete" in delete_btn.inner_text().lower()

    def test_admin_users_shows_user_boxes(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_users.php")
        # admin_users.php always shows at least the admin user
        boxes = page.locator("section.users .box")
        assert boxes.count() > 0, "Admin users page should show at least one user"

    def test_admin_users_box_has_required_fields(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_users.php")
        boxes = page.locator("section.users .box")
        assert boxes.count() > 0
        text = boxes.first.inner_text().lower()
        # admin_users.php: user id, username, email, user type
        for field in ["user id", "username", "email", "user type"]:
            assert field in text, f"User box missing field: '{field}'"

    def test_admin_users_delete_button_exists(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_users.php")
        boxes = page.locator("section.users .box")
        assert boxes.count() > 0
        # admin_users.php: <a href="admin_users.php?delete=..." class="delete-btn">
        delete_btn = boxes.first.locator("a.delete-btn")
        expect(delete_btn).to_be_visible()

    def test_admin_header_shows_admin_name(self, page: Page):
        login_as_admin(page)
        page.goto(f"{BASE_URL}/admin_page.php")
        # admin_header.php: .account-box with username and email spans
        account_box = page.locator(".account-box")
        assert account_box.count() > 0, "Admin account box not found in header"
        text = account_box.inner_text().lower()
        assert "username" in text and "email" in text, \
            "Admin account box should show username and email"


# ─────────────────────────────────────────────
# 10. CONTACT FORM TESTS
# ─────────────────────────────────────────────

class TestContactForm:

    def test_contact_form_visible(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        expect(page.locator("form")).to_be_visible()

    def test_contact_form_has_all_fields(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        # contact.php: name, email, number, message, send
        expect(page.locator("input[name='name']")).to_be_visible()
        expect(page.locator("input[name='email']")).to_be_visible()
        expect(page.locator("input[name='number']")).to_be_visible()
        expect(page.locator("textarea[name='message']")).to_be_visible()
        expect(page.locator("input[name='send']")).to_be_visible()

    def test_contact_number_accepts_only_11_digits(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        # contact.php: maxlength="11", pattern="[0-9]{11}",
        # oninput strips non-digits via JS
        number_input = page.locator("input[name='number']")
        assert number_input.get_attribute("maxlength") == "11", \
            "Number field should have maxlength=11"
        assert number_input.get_attribute("pattern") == "[0-9]{11}", \
            "Number field should have pattern=[0-9]{11}"

    def test_contact_form_submission_success(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        import time
        unique = str(int(time.time()))
        page.fill("input[name='name']",       "Test User")
        page.fill("input[name='email']",      "test@test.com")
        page.fill("input[name='number']",     "01012345678")
        page.fill("textarea[name='message']", f"Automated test message {unique}")
        page.click("input[name='send']")
        page.wait_for_load_state("networkidle")
        # contact.php outputs exactly: 'message sent successfully!'
        assert "message sent successfully!" in page.content(), \
            "No success message after contact form submission"

    def test_contact_form_duplicate_blocked(self, page: Page):
        """Submitting the exact same message twice shows the duplicate warning."""
        login_as_user(page)
        msg_text = "Duplicate test message fixed"
        for _ in range(2):
            page.goto(f"{BASE_URL}/contact.php")
            page.fill("input[name='name']",       "Test User")
            page.fill("input[name='email']",      "test@test.com")
            page.fill("input[name='number']",     "01012345678")
            page.fill("textarea[name='message']", msg_text)
            page.click("input[name='send']")
            page.wait_for_load_state("networkidle")
        # contact.php outputs exactly: 'message sent already!'
        assert "message sent already!" in page.content(), \
            "Duplicate message should be rejected with 'message sent already!'"

    def test_contact_number_field_rejects_letters(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        # contact.php: oninput="this.value = this.value.replace(/[^0-9]/g, '')"
        number_input = page.locator("input[name='number']")
        number_input.fill("abcde12345")
        page.wait_for_timeout(300)
        value = number_input.input_value()
        assert value.isdigit() or value == "", \
            f"Number field should strip non-digit characters, got: '{value}'"

    def test_contact_invalid_phone_length_shows_error(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/contact.php")
        page.fill("input[name='name']",       "Test User")
        page.fill("input[name='email']",      "test@test.com")
        page.fill("input[name='number']",     "0101234")  # only 7 digits — too short
        page.fill("textarea[name='message']", "Test message for short number")
        page.click("input[name='send']")
        page.wait_for_load_state("networkidle")
        # contact.php server-side: 'Phone number must be exactly 11 digits!'
        # (HTML5 pattern may also block — either way it should not say 'sent successfully')
        assert "message sent successfully!" not in page.content(), \
            "A 7-digit phone number should not result in a successful submission"


# ─────────────────────────────────────────────
# 11. ABOUT PAGE TESTS
# ─────────────────────────────────────────────

class TestAboutPage:

    def test_about_page_loads(self, page: Page):
        login_as_user(page)
        response = page.goto(f"{BASE_URL}/about.php")
        assert response.status < 400
        assert "Fatal error"  not in page.content()
        assert "query failed" not in page.content()

    def test_about_redirects_when_logged_out(self, page: Page):
        # about.php: header('location:login.php') without exit() — redirect still fires
        page.goto(f"{BASE_URL}/about.php")
        page.wait_for_load_state("networkidle")
        assert "login.php" in page.url

    def test_about_has_heading(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <div class="heading"><h3>about us</h3>
        heading = page.locator(".heading h3")
        expect(heading).to_be_visible()
        assert "about" in heading.inner_text().lower()

    def test_about_has_reviews_section(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <section class="reviews">
        reviews = page.locator("section.reviews")
        expect(reviews).to_be_visible()

    def test_about_has_exactly_six_review_boxes(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: 6 hardcoded .box elements inside section.reviews
        boxes = page.locator("section.reviews .box")
        assert boxes.count() == 6, \
            f"Reviews section should have exactly 6 boxes, found {boxes.count()}"

    def test_about_review_boxes_have_stars(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: each .box has <div class="stars">
        star_divs = page.locator("section.reviews .box .stars")
        assert star_divs.count() == 6, "Each of the 6 review boxes should have a .stars div"

    def test_about_has_why_choose_us_heading(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <section class="about"> contains <h3>why choose us?</h3>
        heading = page.locator("section.about h3")
        expect(heading).to_be_visible()
        assert "why choose us" in heading.inner_text().lower()

    def test_about_has_contact_us_button(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <a href="contact.php" class="btn">contact us</a>
        btn = page.locator("a.btn[href='contact.php']")
        expect(btn).to_be_visible()
        assert "contact" in btn.inner_text().lower()

    def test_about_has_product_image(self, page: Page):
        login_as_user(page)
        page.goto(f"{BASE_URL}/about.php")
        # about.php: <div class="image"><img src="images/about-img.jpg">
        img = page.locator("section.about .image img")
        assert img.count() > 0, "About section should have an image"


