<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
include 'db-ayar.php';
if (is_file(__DIR__ . '/storage-media.php')) {
	require_once __DIR__ . '/storage-media.php';
}
if (!function_exists('media_url')) {
	function media_url($imgValue, $default = 'upload/no-image.jpg') {
		$imgValue = trim((string)$imgValue);
		if ($imgValue === '') {
			return $default;
		}
		if (preg_match('#^https?://#i', $imgValue)) {
			return $imgValue;
		}
		return 'upload/' . basename($imgValue);
	}
}
if (!function_exists('media_panel_url')) {
	function media_panel_url($imgValue, $default = '../upload/no-image.jpg') {
		$imgValue = trim((string)$imgValue);
		if ($imgValue === '') {
			return $default;
		}
		if (preg_match('#^https?://#i', $imgValue)) {
			return $imgValue;
		}
		return '../upload/' . basename($imgValue);
	}
}
if (!function_exists('media_url_absolute')) {
	function media_url_absolute($imgValue) {
		global $site;
		$u = media_url($imgValue, '');
		if ($u === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $u)) {
			return $u;
		}
		return rtrim($site, '/') . '/' . ltrim($u, '/');
	}
}
if (!function_exists('media_upload_product_image')) {
	function media_upload_product_image($tmpPath, $ext) {
		$ext = strtolower((string)$ext);
		$uploadDir = __DIR__ . '/../upload';
		if (!is_dir($uploadDir)) {
			@mkdir($uploadDir, 0755, true);
		}
		$baseName = time() . '-' . uniqid('', true);
		require_once __DIR__ . '/image-optimizer.php';
		$savedName = btm_optimize_image($tmpPath, $uploadDir, $baseName, $ext);
		if ($savedName === false) {
			return ['ok' => false, 'error' => 'optimize_failed'];
		}
		return ['ok' => true, 'value' => $savedName, 'storage' => 'local'];
	}
}
if (!function_exists('media_delete_if_orphan')) {
	function media_delete_if_orphan(PDO $db, $imgValue) {
		$imgValue = trim((string)$imgValue);
		if ($imgValue === '' || stripos($imgValue, 'no-image') !== false) {
			return false;
		}
		if (preg_match('#^https?://#i', $imgValue)) {
			return false;
		}
		if (preg_match('#[/\\\\]#', $imgValue)) {
			return false;
		}
		try {
			$q = $db->prepare('SELECT COUNT(*) FROM urun_img WHERE img = ?');
			$q->execute([$imgValue]);
			if ((int)$q->fetchColumn() > 0) {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
		$filePath = __DIR__ . '/../upload/' . basename($imgValue);
		if (!is_file($filePath)) {
			return false;
		}
		return @unlink($filePath);
	}
}
$time = time();

$sms_izin = $db->query("SELECT * FROM sms_izinleri LIMIT 1")->fetch(PDO::FETCH_ASSOC);


// Çeviri fonksiyonu
function t($key, $language = 'tr') {
    $translations = [
        'tr' => [
            'home' => 'Anasayfa',
            'categories' => 'Kategoriler',
            'products' => 'Ürünler',
            'cart' => 'Sepet',
            'favorites' => 'Favoriler',
            'search' => 'Ara',
            'search_placeholder' => 'Binlerce ürün arasında arayın...',
            'all_categories' => 'Tüm Kategoriler',
            'sign_up' => 'Kayıt Ol',
            'sign_in' => 'Giriş Yap',
            'my_account' => 'Hesabım',
            'order_tracking' => 'Sipariş Takibi',
            'payment_notification' => 'Ödeme Bildirimi',
            'bank_accounts' => 'Banka Hesapları',
            'blog' => 'Blog',
            'or' => 'veya',
            'stock_code' => 'Stok Kodu',
            'stock_status' => 'Stok Durumu',
            'in_stock' => 'Stokta Var',
            'out_of_stock' => 'Stokta Yok',
            'add_to_cart' => 'Sepete Ekle',
            'price' => 'Fiyat',
            'description' => 'Açıklama',
            'details' => 'Detaylar',
            'category' => 'Kategori',
            'brand' => 'Marka',
            'related_products' => 'Benzer Ürünler',
            'recommended_products' => 'Önerilen Diğer Ürünler',
            'reference_numbers' => 'Referans Numaraları',
            'color_options' => 'Renk Seçenekleri',
            'select_color' => 'Renk Seçiniz',
            'color' => 'Renk',
            'add_review' => 'Yorum Ekle',
            'your_review' => 'Yorumunuz',
            'support' => 'Destek',
            'hover_for_details' => 'Detaylar için üzerine gelin',
            'cart_page' => 'Sepetim',
            'product' => 'Ürün',
            'unit_price' => 'Birim Fiyat',
            'quantity' => 'Adet',
            'total' => 'Toplam',
            'action' => 'İşlem',
            'remove' => 'Sil',
            'continue_shopping' => 'Alışverişe Devam Et',
            'checkout' => 'Ödemeye Geç',
            'subtotal' => 'Ara Toplam',
            'shipping' => 'Kargo',
            'tax' => 'KDV',
            'grand_total' => 'Genel Toplam',
            'not_member' => 'Üye Değil Misiniz ?',
            'dont_miss_opportunities' => 'Fırsatları kaçırmamak için sen de üye ol!',
            'secure_payment' => 'Güvenli Ödeme',
            'enjoyable_shopping' => 'Keyifli Alışveriş',
            'free_easy_register' => 'Ücretsiz ve kolay kayıt ol',
            'fast_secure_shopping' => 'Hızlı ve güvenli alışverişin yeni adresi',
            'already_member' => 'Zaten Üye Misiniz ?',
            'click_to_login' => 'Giriş yap butonuna tıklayarak giriş yapabilirsiniz.',
            'name' => 'Ad',
            'surname' => 'Soyad',
            'phone' => 'Telefon',
            'email' => 'E-posta',
            'password' => 'Şifre',
            'confirm_password' => 'Şifre Tekrar',
            'register' => 'Kayıt Ol',
            'login' => 'Giriş Yap',
            'email_already_used' => 'Bu email adresi zaten kullanılıyor.',
            'registration_success' => 'Kayıt işlemi başarılı.',
            'login_page' => 'Giriş Yap',
            'forgot_password' => 'Şifremi Unuttum',
            'stay_logged_in' => 'Beni Hatırla',
            'get_notified' => 'Kampanyalardan Haberdar Olun',
            'email_address' => 'Email Adres',
            'most_searched' => 'En Çok Arananlar',
            'customer_service' => 'Müşteri Hizmetleri',
            'our_address' => 'Adresimiz',
            'popular_categories' => 'Popüler Kategoriler',
            'pages' => 'Sayfalar',
            'quick_access' => 'Hızlı Erişim',
            'contact' => 'İletişim',
            'copyright' => '© Bu site BT MOTORSHOP® yazılım ekibi tarafından hazırlanmıştır.',
            'list_price_eur' => 'Liste Fiyatı (Euro)',
            'list_price_tl' => 'Liste Fiyatı (TL)',
            'discount_rate' => 'İskonto Oranı',
            'net_currency_price' => 'Net Döviz Fiyatı',
            'net_price_no_tax' => 'KDV\'siz Net Fiyat',
            'net_price_with_tax' => 'Net Fiyat KDV Dahil',
            'early_payment_prices' => 'Erken Ödeme Birim Fiyatları',
            'credit_card' => 'Kredi Kartı',
            'cash_payment' => 'Peşin Ödeme'
        ],
        'en' => [
            'home' => 'Home',
            'categories' => 'Categories',
            'products' => 'Products',
            'cart' => 'Cart',
            'favorites' => 'Favorites',
            'search' => 'Search',
            'search_placeholder' => 'Search among thousands of products...',
            'all_categories' => 'All Categories',
            'sign_up' => 'Sign Up',
            'sign_in' => 'Sign In',
            'my_account' => 'My Account',
            'order_tracking' => 'Order Tracking',
            'payment_notification' => 'Payment Notification',
            'bank_accounts' => 'Bank Accounts',
            'blog' => 'Blog',
            'or' => 'or',
            'stock_code' => 'Stock Code',
            'stock_status' => 'Stock Status',
            'in_stock' => 'In Stock',
            'out_of_stock' => 'Out of Stock',
            'add_to_cart' => 'Add to Cart',
            'price' => 'Price',
            'description' => 'Description',
            'details' => 'Details',
            'category' => 'Category',
            'brand' => 'Brand',
            'related_products' => 'Related Products',
            'recommended_products' => 'Recommended Products',
            'reference_numbers' => 'Reference Numbers',
            'color_options' => 'Color Options',
            'select_color' => 'Select Color',
            'color' => 'Color',
            'add_review' => 'Add Review',
            'your_review' => 'Your Review',
            'support' => 'Support',
            'hover_for_details' => 'Hover for details',
            'cart_page' => 'My Cart',
            'product' => 'Product',
            'unit_price' => 'Unit Price',
            'quantity' => 'Quantity',
            'total' => 'Total',
            'action' => 'Action',
            'remove' => 'Remove',
            'continue_shopping' => 'Continue Shopping',
            'checkout' => 'Checkout',
            'subtotal' => 'Subtotal',
            'shipping' => 'Shipping',
            'tax' => 'Tax',
            'grand_total' => 'Grand Total',
            'not_member' => 'Not a Member?',
            'dont_miss_opportunities' => 'Don\'t miss out on opportunities, become a member!',
            'secure_payment' => 'Secure Payment',
            'enjoyable_shopping' => 'Enjoyable Shopping',
            'free_easy_register' => 'Free and easy registration',
            'fast_secure_shopping' => 'Fast and secure shopping\'s new address',
            'already_member' => 'Already a Member?',
            'click_to_login' => 'You can login by clicking the login button.',
            'name' => 'Name',
            'surname' => 'Surname',
            'phone' => 'Phone',
            'email' => 'Email',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password',
            'register' => 'Register',
            'login' => 'Login',
            'email_already_used' => 'This email address is already in use.',
            'registration_success' => 'Registration successful.',
            'login_page' => 'Login',
            'forgot_password' => 'Forgot Password',
            'stay_logged_in' => 'Remember Me',
            'get_notified' => 'Get Notified About Campaigns',
            'email_address' => 'Email Address',
            'most_searched' => 'Most Searched',
            'customer_service' => 'Customer Service',
            'our_address' => 'Our Address',
            'popular_categories' => 'Popular Categories',
            'pages' => 'Pages',
            'quick_access' => 'Quick Access',
            'contact' => 'Contact',
            'copyright' => '© This site was prepared by BT MOTORSHOP® software team.',
            'list_price_eur' => 'List Price (Euro)',
            'list_price_tl' => 'List Price (TL)',
            'discount_rate' => 'Discount Rate',
            'net_currency_price' => 'Net Currency Price',
            'net_price_no_tax' => 'Net Price No Tax',
            'net_price_with_tax' => 'Net Price With Tax',
            'early_payment_prices' => 'Early Payment Unit Prices',
            'credit_card' => 'Credit Card',
            'cash_payment' => 'Cash Payment'
        ],
        'ru' => [
            'home' => 'Главная',
            'categories' => 'Категории',
            'products' => 'Товары',
            'cart' => 'Корзина',
            'favorites' => 'Избранное',
            'search' => 'Поиск',
            'search_placeholder' => 'Поиск среди тысяч товаров...',
            'all_categories' => 'Все категории',
            'sign_up' => 'Регистрация',
            'sign_in' => 'Войти',
            'my_account' => 'Мой аккаунт',
            'order_tracking' => 'Отслеживание заказа',
            'payment_notification' => 'Уведомление об оплате',
            'bank_accounts' => 'Банковские счета',
            'blog' => 'Блог',
            'or' => 'или',
            'stock_code' => 'Код склада',
            'stock_status' => 'Статус склада',
            'in_stock' => 'В наличии',
            'out_of_stock' => 'Нет в наличии',
            'add_to_cart' => 'Добавить в корзину',
            'price' => 'Цена',
            'description' => 'Описание',
            'details' => 'Детали',
            'category' => 'Категория',
            'brand' => 'Бренд',
            'related_products' => 'Похожие товары',
            'recommended_products' => 'Рекомендуемые товары',
            'reference_numbers' => 'Номера ссылок',
            'color_options' => 'Варианты цвета',
            'select_color' => 'Выберите цвет',
            'color' => 'Цвет',
            'add_review' => 'Добавить отзыв',
            'your_review' => 'Ваш отзыв',
            'support' => 'Поддержка',
            'hover_for_details' => 'Наведите для деталей',
            'cart_page' => 'Моя корзина',
            'product' => 'Товар',
            'unit_price' => 'Цена за единицу',
            'quantity' => 'Количество',
            'total' => 'Итого',
            'action' => 'Действие',
            'remove' => 'Удалить',
            'continue_shopping' => 'Продолжить покупки',
            'checkout' => 'Оформить заказ',
            'subtotal' => 'Промежуточный итог',
            'shipping' => 'Доставка',
            'tax' => 'НДС',
            'grand_total' => 'Общая сумма',
            'not_member' => 'Не являетесь членом?',
            'dont_miss_opportunities' => 'Не упустите возможности, станьте участником!',
            'secure_payment' => 'Безопасная оплата',
            'enjoyable_shopping' => 'Приятные покупки',
            'free_easy_register' => 'Бесплатная и простая регистрация',
            'fast_secure_shopping' => 'Новый адрес быстрых и безопасных покупок',
            'already_member' => 'Уже являетесь участником?',
            'click_to_login' => 'Вы можете войти, нажав кнопку входа.',
            'name' => 'Имя',
            'surname' => 'Фамилия',
            'phone' => 'Телефон',
            'email' => 'Электронная почта',
            'password' => 'Пароль',
            'confirm_password' => 'Подтвердите пароль',
            'register' => 'Регистрация',
            'login' => 'Войти',
            'email_already_used' => 'Этот адрес электронной почты уже используется.',
            'registration_success' => 'Регистрация успешна.',
            'login_page' => 'Вход',
            'forgot_password' => 'Забыли пароль',
            'stay_logged_in' => 'Запомнить меня',
            'get_notified' => 'Получайте уведомления о кампаниях',
            'email_address' => 'Адрес электронной почты',
            'most_searched' => 'Самые популярные',
            'customer_service' => 'Служба поддержки клиентов',
            'our_address' => 'Наш адрес',
            'popular_categories' => 'Популярные категории',
            'pages' => 'Страницы',
            'quick_access' => 'Быстрый доступ',
            'contact' => 'Контакты',
            'copyright' => '© Этот сайт был подготовлен командой разработчиков BT MOTORSHOP®.',
            'list_price_eur' => 'Цена по прайсу (Евро)',
            'list_price_tl' => 'Цена по прайсу (TL)',
            'discount_rate' => 'Процент скидки',
            'net_currency_price' => 'Чистая цена в валюте',
            'net_price_no_tax' => 'Чистая цена без НДС',
            'net_price_with_tax' => 'Чистая цена с НДС',
            'early_payment_prices' => 'Цены за раннюю оплату',
            'credit_card' => 'Кредитная карта',
            'cash_payment' => 'Наличный платеж'
        ],
        'fr' => [
            'home' => 'Accueil',
            'categories' => 'Catégories',
            'products' => 'Produits',
            'cart' => 'Panier',
            'favorites' => 'Favoris',
            'search' => 'Rechercher',
            'search_placeholder' => 'Recherchez parmi des milliers de produits...',
            'all_categories' => 'Toutes les catégories',
            'sign_up' => 'S\'inscrire',
            'sign_in' => 'Se connecter',
            'my_account' => 'Mon compte',
            'order_tracking' => 'Suivi de commande',
            'payment_notification' => 'Notification de paiement',
            'bank_accounts' => 'Comptes bancaires',
            'blog' => 'Blog',
            'or' => 'ou',
            'stock_code' => 'Code de stock',
            'stock_status' => 'Statut du stock',
            'in_stock' => 'En stock',
            'out_of_stock' => 'Rupture de stock',
            'add_to_cart' => 'Ajouter au panier',
            'price' => 'Prix',
            'description' => 'Description',
            'details' => 'Détails',
            'category' => 'Catégorie',
            'brand' => 'Marque',
            'related_products' => 'Produits connexes',
            'recommended_products' => 'Produits recommandés',
            'reference_numbers' => 'Numéros de référence',
            'color_options' => 'Options de couleur',
            'select_color' => 'Sélectionner la couleur',
            'color' => 'Couleur',
            'add_review' => 'Ajouter un avis',
            'your_review' => 'Votre avis',
            'support' => 'Support',
            'hover_for_details' => 'Survolez pour les détails',
            'cart_page' => 'Mon panier',
            'product' => 'Produit',
            'unit_price' => 'Prix unitaire',
            'quantity' => 'Quantité',
            'total' => 'Total',
            'action' => 'Action',
            'remove' => 'Supprimer',
            'continue_shopping' => 'Continuer les achats',
            'checkout' => 'Passer à la caisse',
            'subtotal' => 'Sous-total',
            'shipping' => 'Livraison',
            'tax' => 'TVA',
            'grand_total' => 'Total général',
            'not_member' => 'Pas encore membre?',
            'dont_miss_opportunities' => 'Ne manquez pas les opportunités, devenez membre!',
            'secure_payment' => 'Paiement sécurisé',
            'enjoyable_shopping' => 'Shopping agréable',
            'free_easy_register' => 'Inscription gratuite et facile',
            'fast_secure_shopping' => 'Nouvelle adresse de shopping rapide et sécurisé',
            'already_member' => 'Déjà membre?',
            'click_to_login' => 'Vous pouvez vous connecter en cliquant sur le bouton de connexion.',
            'name' => 'Prénom',
            'surname' => 'Nom',
            'phone' => 'Téléphone',
            'email' => 'E-mail',
            'password' => 'Mot de passe',
            'confirm_password' => 'Confirmer le mot de passe',
            'register' => 'S\'inscrire',
            'login' => 'Se connecter',
            'email_already_used' => 'Cette adresse e-mail est déjà utilisée.',
            'registration_success' => 'Inscription réussie.',
            'login_page' => 'Connexion',
            'forgot_password' => 'Mot de passe oublié',
            'stay_logged_in' => 'Se souvenir de moi',
            'get_notified' => 'Soyez informé des campagnes',
            'email_address' => 'Adresse e-mail',
            'most_searched' => 'Les plus recherchés',
            'customer_service' => 'Service client',
            'our_address' => 'Notre adresse',
            'popular_categories' => 'Catégories populaires',
            'pages' => 'Pages',
            'quick_access' => 'Accès rapide',
            'contact' => 'Contact',
            'copyright' => '© Ce site a été préparé par l\'équipe logicielle BT MOTORSHOP®.',
            'list_price_eur' => 'Prix de liste (Euro)',
            'list_price_tl' => 'Prix de liste (TL)',
            'discount_rate' => 'Taux de remise',
            'net_currency_price' => 'Prix net en devise',
            'net_price_no_tax' => 'Prix net sans TVA',
            'net_price_with_tax' => 'Prix net avec TVA',
            'early_payment_prices' => 'Prix unitaires de paiement anticipé',
            'credit_card' => 'Carte de crédit',
            'cash_payment' => 'Paiement en espèces'
        ],
        'es' => [
            'home' => 'Inicio',
            'categories' => 'Categorías',
            'products' => 'Productos',
            'cart' => 'Carrito',
            'favorites' => 'Favoritos',
            'search' => 'Buscar',
            'search_placeholder' => 'Buscar entre miles de productos...',
            'all_categories' => 'Todas las categorías',
            'sign_up' => 'Registrarse',
            'sign_in' => 'Iniciar sesión',
            'my_account' => 'Mi cuenta',
            'order_tracking' => 'Seguimiento de pedidos',
            'payment_notification' => 'Notificación de pago',
            'bank_accounts' => 'Cuentas bancarias',
            'blog' => 'Blog',
            'or' => 'o',
            'stock_code' => 'Código de stock',
            'stock_status' => 'Estado del stock',
            'in_stock' => 'En stock',
            'out_of_stock' => 'Agotado',
            'add_to_cart' => 'Añadir al carrito',
            'price' => 'Precio',
            'description' => 'Descripción',
            'details' => 'Detalles',
            'category' => 'Categoría',
            'brand' => 'Marca',
            'related_products' => 'Productos relacionados',
            'recommended_products' => 'Productos recomendados',
            'reference_numbers' => 'Números de referencia',
            'color_options' => 'Opciones de color',
            'select_color' => 'Seleccionar color',
            'color' => 'Color',
            'add_review' => 'Añadir reseña',
            'your_review' => 'Tu reseña',
            'support' => 'Soporte',
            'hover_for_details' => 'Pase el mouse para detalles',
            'cart_page' => 'Mi carrito',
            'product' => 'Producto',
            'unit_price' => 'Precio unitario',
            'quantity' => 'Cantidad',
            'total' => 'Total',
            'action' => 'Acción',
            'remove' => 'Eliminar',
            'continue_shopping' => 'Continuar comprando',
            'checkout' => 'Finalizar compra',
            'subtotal' => 'Subtotal',
            'shipping' => 'Envío',
            'tax' => 'Impuesto',
            'grand_total' => 'Total general',
            'not_member' => '¿No eres miembro?',
            'dont_miss_opportunities' => '¡No te pierdas las oportunidades, conviértete en miembro!',
            'secure_payment' => 'Pago seguro',
            'enjoyable_shopping' => 'Compras agradables',
            'free_easy_register' => 'Registro gratuito y fácil',
            'fast_secure_shopping' => 'Nueva dirección de compras rápidas y seguras',
            'already_member' => '¿Ya eres miembro?',
            'click_to_login' => 'Puedes iniciar sesión haciendo clic en el botón de inicio de sesión.',
            'name' => 'Nombre',
            'surname' => 'Apellido',
            'phone' => 'Teléfono',
            'email' => 'Correo electrónico',
            'password' => 'Contraseña',
            'confirm_password' => 'Confirmar contraseña',
            'register' => 'Registrarse',
            'login' => 'Iniciar sesión',
            'email_already_used' => 'Esta dirección de correo electrónico ya está en uso.',
            'registration_success' => 'Registro exitoso.',
            'login_page' => 'Iniciar sesión',
            'forgot_password' => 'Olvidé mi contraseña',
            'stay_logged_in' => 'Recordarme',
            'get_notified' => 'Mantente informado sobre campañas',
            'email_address' => 'Dirección de correo electrónico',
            'most_searched' => 'Más buscados',
            'customer_service' => 'Servicio al cliente',
            'our_address' => 'Nuestra dirección',
            'popular_categories' => 'Categorías populares',
            'pages' => 'Páginas',
            'quick_access' => 'Acceso rápido',
            'contact' => 'Contacto',
            'copyright' => '© Este sitio fue preparado por el equipo de software BT MOTORSHOP®.',
            'list_price_eur' => 'Precio de lista (Euro)',
            'list_price_tl' => 'Precio de lista (TL)',
            'discount_rate' => 'Tasa de descuento',
            'net_currency_price' => 'Precio neto en moneda',
            'net_price_no_tax' => 'Precio neto sin impuestos',
            'net_price_with_tax' => 'Precio neto con impuestos',
            'early_payment_prices' => 'Precios unitarios de pago anticipado',
            'credit_card' => 'Tarjeta de crédito',
            'cash_payment' => 'Pago en efectivo'
        ],
        'ar' => [
            'home' => 'الرئيسية',
            'categories' => 'الفئات',
            'products' => 'المنتجات',
            'cart' => 'السلة',
            'favorites' => 'المفضلة',
            'search' => 'بحث',
            'search_placeholder' => 'ابحث بين آلاف المنتجات...',
            'all_categories' => 'جميع الفئات',
            'sign_up' => 'التسجيل',
            'sign_in' => 'تسجيل الدخول',
            'my_account' => 'حسابي',
            'order_tracking' => 'تتبع الطلب',
            'payment_notification' => 'إشعار الدفع',
            'bank_accounts' => 'الحسابات المصرفية',
            'blog' => 'المدونة',
            'or' => 'أو',
            'stock_code' => 'رمز المخزون',
            'stock_status' => 'حالة المخزون',
            'in_stock' => 'متوفر',
            'out_of_stock' => 'غير متوفر',
            'add_to_cart' => 'أضف إلى السلة',
            'price' => 'السعر',
            'description' => 'الوصف',
            'details' => 'التفاصيل',
            'category' => 'الفئة',
            'brand' => 'العلامة التجارية',
            'related_products' => 'منتجات ذات صلة',
            'recommended_products' => 'المنتجات الموصى بها',
            'reference_numbers' => 'أرقام المرجع',
            'color_options' => 'خيارات اللون',
            'select_color' => 'اختر اللون',
            'color' => 'اللون',
            'add_review' => 'إضافة مراجعة',
            'your_review' => 'مراجعتك',
            'support' => 'الدعم',
            'hover_for_details' => 'مرر للتفاصيل',
            'cart_page' => 'سلتي',
            'product' => 'المنتج',
            'unit_price' => 'السعر الوحدة',
            'quantity' => 'الكمية',
            'total' => 'المجموع',
            'action' => 'الإجراء',
            'remove' => 'إزالة',
            'continue_shopping' => 'متابعة التسوق',
            'checkout' => 'الدفع',
            'subtotal' => 'المجموع الفرعي',
            'shipping' => 'الشحن',
            'tax' => 'الضريبة',
            'grand_total' => 'المجموع الكلي',
            'not_member' => 'لست عضواً؟',
            'dont_miss_opportunities' => 'لا تفوت الفرص، انضم الآن!',
            'secure_payment' => 'دفع آمن',
            'enjoyable_shopping' => 'تسوق ممتع',
            'free_easy_register' => 'تسجيل مجاني وسهل',
            'fast_secure_shopping' => 'عنوان التسوق السريع والآمن الجديد',
            'already_member' => 'عضواً بالفعل؟',
            'click_to_login' => 'يمكنك تسجيل الدخول بالنقر على زر تسجيل الدخول.',
            'name' => 'الاسم',
            'surname' => 'اسم العائلة',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'confirm_password' => 'تأكيد كلمة المرور',
            'register' => 'التسجيل',
            'login' => 'تسجيل الدخول',
            'email_already_used' => 'عنوان البريد الإلكتروني هذا مستخدم بالفعل.',
            'registration_success' => 'تم التسجيل بنجاح.',
            'login_page' => 'تسجيل الدخول',
            'forgot_password' => 'نسيت كلمة المرور',
            'stay_logged_in' => 'تذكرني',
            'get_notified' => 'كن على اطلاع بالحملات',
            'email_address' => 'عنوان البريد الإلكتروني',
            'most_searched' => 'الأكثر بحثاً',
            'customer_service' => 'خدمة العملاء',
            'our_address' => 'عنواننا',
            'popular_categories' => 'الفئات الشائعة',
            'pages' => 'الصفحات',
            'quick_access' => 'الوصول السريع',
            'contact' => 'اتصل بنا',
            'copyright' => '© تم إعداد هذا الموقع بواسطة فريق برمجيات BT MOTORSHOP®.',
            'list_price_eur' => 'سعر القائمة (يورو)',
            'list_price_tl' => 'سعر القائمة (TL)',
            'discount_rate' => 'معدل الخصم',
            'net_currency_price' => 'السعر الصافي بالعملة',
            'net_price_no_tax' => 'السعر الصافي بدون ضريبة',
            'net_price_with_tax' => 'السعر الصافي مع الضريبة',
            'early_payment_prices' => 'أسعار الوحدة للدفع المبكر',
            'credit_card' => 'بطاقة الائتمان',
            'cash_payment' => 'الدفع النقدي'
        ],
        'pl' => [
            'home' => 'Strona główna',
            'categories' => 'Kategorie',
            'products' => 'Produkty',
            'cart' => 'Koszyk',
            'favorites' => 'Ulubione',
            'search' => 'Szukaj',
            'search_placeholder' => 'Szukaj wśród tysięcy produktów...',
            'all_categories' => 'Wszystkie kategorie',
            'sign_up' => 'Zarejestruj się',
            'sign_in' => 'Zaloguj się',
            'my_account' => 'Moje konto',
            'order_tracking' => 'Śledzenie zamówienia',
            'payment_notification' => 'Powiadomienie o płatności',
            'bank_accounts' => 'Konta bankowe',
            'blog' => 'Blog',
            'or' => 'lub',
            'stock_code' => 'Kod magazynowy',
            'stock_status' => 'Status magazynu',
            'in_stock' => 'W magazynie',
            'out_of_stock' => 'Brak w magazynie',
            'add_to_cart' => 'Dodaj do koszyka',
            'price' => 'Cena',
            'description' => 'Opis',
            'details' => 'Szczegóły',
            'category' => 'Kategoria',
            'brand' => 'Marka',
            'related_products' => 'Powiązane produkty',
            'recommended_products' => 'Polecane produkty',
            'reference_numbers' => 'Numery referencyjne',
            'color_options' => 'Opcje kolorów',
            'select_color' => 'Wybierz kolor',
            'color' => 'Kolor',
            'add_review' => 'Dodaj recenzję',
            'your_review' => 'Twoja recenzja',
            'support' => 'Wsparcie',
            'hover_for_details' => 'Najedź, aby zobaczyć szczegóły',
            'cart_page' => 'Mój koszyk',
            'product' => 'Produkt',
            'unit_price' => 'Cena jednostkowa',
            'quantity' => 'Ilość',
            'total' => 'Razem',
            'action' => 'Akcja',
            'remove' => 'Usuń',
            'continue_shopping' => 'Kontynuuj zakupy',
            'checkout' => 'Przejdź do kasy',
            'subtotal' => 'Suma częściowa',
            'shipping' => 'Wysyłka',
            'tax' => 'Podatek',
            'grand_total' => 'Suma całkowita',
            'not_member' => 'Nie jesteś członkiem?',
            'dont_miss_opportunities' => 'Nie przegap okazji, zostań członkiem!',
            'secure_payment' => 'Bezpieczna płatność',
            'enjoyable_shopping' => 'Przyjemne zakupy',
            'free_easy_register' => 'Darmowa i łatwa rejestracja',
            'fast_secure_shopping' => 'Nowy adres szybkich i bezpiecznych zakupów',
            'already_member' => 'Już jesteś członkiem?',
            'click_to_login' => 'Możesz się zalogować, klikając przycisk logowania.',
            'name' => 'Imię',
            'surname' => 'Nazwisko',
            'phone' => 'Telefon',
            'email' => 'E-mail',
            'password' => 'Hasło',
            'confirm_password' => 'Potwierdź hasło',
            'register' => 'Zarejestruj się',
            'login' => 'Zaloguj się',
            'email_already_used' => 'Ten adres e-mail jest już używany.',
            'registration_success' => 'Rejestracja zakończona sukcesem.',
            'login_page' => 'Logowanie',
            'forgot_password' => 'Zapomniałem hasła',
            'stay_logged_in' => 'Zapamiętaj mnie',
            'get_notified' => 'Bądź na bieżąco z kampaniami',
            'email_address' => 'Adres e-mail',
            'most_searched' => 'Najczęściej wyszukiwane',
            'customer_service' => 'Obsługa klienta',
            'our_address' => 'Nasz adres',
            'popular_categories' => 'Popularne kategorie',
            'pages' => 'Strony',
            'quick_access' => 'Szybki dostęp',
            'contact' => 'Kontakt',
            'copyright' => '© Ta strona została przygotowana przez zespół oprogramowania BT MOTORSHOP®.',
            'list_price_eur' => 'Cena katalogowa (Euro)',
            'list_price_tl' => 'Cena katalogowa (TL)',
            'discount_rate' => 'Wskaźnik rabatu',
            'net_currency_price' => 'Cena netto w walucie',
            'net_price_no_tax' => 'Cena netto bez podatku',
            'net_price_with_tax' => 'Cena netto z podatkiem',
            'early_payment_prices' => 'Ceny jednostkowe za wczesną płatność',
            'credit_card' => 'Karta kredytowa',
            'cash_payment' => 'Płatność gotówką'
        ]
    ];
    
    if (isset($translations[$language][$key])) {
        return $translations[$language][$key];
    }
    
    // Fallback to Turkish if translation not found
    return isset($translations['tr'][$key]) ? $translations['tr'][$key] : $key;
}

function email_send($email,$content,$subject){
    global $db;
    $email_settings = $db->query("SELECT * FROM email_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (is_file('vendor/autoload.php')) {
        require 'vendor/autoload.php';
    }else{
        require '../vendor/autoload.php';
    }
    
    try {
      $mail = new PHPMailer(true);
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->isSMTP();
      $mail->Host       = $email_settings['host'];
      $mail->SMTPAuth   = true;
      $mail->Username   = $email_settings['email'];
      $mail->Password   = $email_settings['password'];
      $mail->SMTPSecure = $email_settings['ssl_'];
      $mail->Port       = $email_settings['port'];
      $mail->CharSet        = 'UTF-8';
      $mail->SMTPDebug  = 0;   
      $mail->setFrom($email_settings['email']);
      $mail->addAddress($email);
      $mail->isHTML(true);
      $mail->Subject        = $subject;
      $mail->Body           = $content;
      $mail->AltBody        = '';
      $mail->send();
    } catch (Exception $e) {
    }
}

function sef($str, $options = array()){
    $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
    $defaults = array('delimiter' => '-','limit' => null,'lowercase' => true,'replacements' => array(),'transliterate' => true);
    $options = array_merge($defaults, $options);
    $char_map = array(
        // Latin
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
        // Latin symbols
        '©' => '(c)',
        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        // Turkish
        'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
        'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
        // Russian
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
        // Ukrainian
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
        // Czech
        'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
        'ž' => 'z',
        // Polish
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
        'ż' => 'z',
        // Latvian
        'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
        'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'š' => 's', 'ū' => 'u', 'ž' => 'z'
    );
    $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
    if ($options['transliterate']) {
        $str = str_replace(array_keys($char_map), $char_map, $str);
    }
    $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
    $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
    $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
    $str = trim($str, $options['delimiter']);
    return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

/**
 * Arama ifadesini kelime / kod parcalarina ayirir (hepsi eslesmeli).
 */
function arama_tokenleri($term) {
    $term = trim((string)$term);
    if ($term === '') {
        return [];
    }
    $parts = preg_split('/[\s\/\(\)\[\]:;,]+/u', $term, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = [];
    foreach ($parts as $part) {
        $part = trim($part, " \t.\-");
        if ($part === '') {
            continue;
        }
        $len = mb_strlen($part, 'UTF-8');
        if ($len >= 2 || preg_match('/\d/', $part)) {
            $tokens[] = $part;
        }
    }
    if (empty($tokens)) {
        return [$term];
    }
    return array_values(array_unique($tokens));
}

/**
 * Turkce uyumlu LIKE desenleri (or. piston -> PISTON, PİSTON).
 */
function arama_like_patterns($token) {
    $token = trim((string)$token);
    if ($token === '') {
        return [];
    }
    $variants = [
        $token,
        mb_strtoupper($token, 'UTF-8'),
        mb_strtolower($token, 'UTF-8'),
    ];
    $variants[] = str_replace(['i', 'ı'], ['İ', 'I'], mb_strtoupper($token, 'UTF-8'));
    $variants[] = str_replace(['I', 'İ'], ['ı', 'i'], mb_strtolower($token, 'UTF-8'));
    $patterns = [];
    foreach ($variants as $v) {
        if ($v !== '') {
            $patterns['%' . $v . '%'] = true;
        }
    }
    return array_keys($patterns);
}

/**
 * Musteri urun aramasi icin WHERE kosulu ve PDO parametreleri.
 *
 * @return array{0: string, 1: array<int, string>}
 */
function arama_urun_sql_kosul($searchTerm) {
    $tokens = arama_tokenleri($searchTerm);
    if (empty($tokens)) {
        return ['1=0', []];
    }
    $fieldMatchSql = '(
        urun.baslik LIKE ?
        OR urun.baslik_en LIKE ?
        OR urun.baslik_ru LIKE ?
        OR urun.stok_kodu LIKE ?
        OR urun.kisa_aciklama LIKE ?
        OR urun.aciklama LIKE ?
        OR EXISTS (
            SELECT 1 FROM urun_referans
            WHERE urun_referans.urun_id = urun.id
              AND urun_referans.referans_no LIKE ?
        )
    )';
    $whereParts = [];
    $params = [];
    foreach ($tokens as $token) {
        $patterns = arama_like_patterns($token);
        if (empty($patterns)) {
            continue;
        }
        $tokenParts = [];
        foreach ($patterns as $pat) {
            $tokenParts[] = $fieldMatchSql;
            for ($i = 0; $i < 7; $i++) {
                $params[] = $pat;
            }
        }
        $whereParts[] = '(' . implode(' OR ', $tokenParts) . ')';
    }
    if (empty($whereParts)) {
        return ['1=0', []];
    }
    return [implode(' AND ', $whereParts), $params];
}

/**
 * urun_img tablosunda referansi kalmayan dosyayi siler (local veya R2).
 */
function urun_resim_dosya_sil_if_orphan(PDO $db, $filename) {
    if (function_exists('media_delete_if_orphan')) {
        return media_delete_if_orphan($db, $filename);
    }
    return false;
}

/**
 * Yedek parca urun adini kural tabanli SEO formatina cevirir (ucretsiz).
 */
/**
 * Bir metinde PARANTEZ DIŞINDAki ilk ":" karakterinin bayt konumunu döner.
 * Parantez/köşeli parantez içindeki ":" (örn. "(STD - ÇAP: 105 mm)") bölme
 * noktası sayılmaz. Bulunmazsa -1 döner.
 * Not: "(" ")" "[" "]" ":" ASCII olduğundan UTF-8 metinde bayt taraması güvenlidir.
 */
function seo_find_top_level_colon($str) {
    $depth = 0;
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $ch = $str[$i];
        if ($ch === '(' || $ch === '[') {
            $depth++;
        } elseif ($ch === ')' || $ch === ']') {
            if ($depth > 0) { $depth--; }
        } elseif ($ch === ':' && $depth === 0) {
            return $i;
        }
    }
    return -1;
}

function seo_optimize_title_rules($title) {
    $title = trim((string)$title);
    if ($title === '') {
        return '';
    }

    // "referans no:XXX" (eski format eki) varsa ayıkla.
    $ref = '';
    if (preg_match('/referans\s*no\s*:\s*([A-Za-z0-9\-_.]+)/iu', $title, $refMatch)) {
        $ref = trim($refMatch[1]);
        $title = preg_replace('/\s*\/\s*referans\s*no\s*:[^\/]*/iu', '', $title);
        $title = preg_replace('/referans\s*no\s*:[A-Za-z0-9\-_.]+/iu', '', $title);
        $title = trim($title);
    }

    // Yeniden sıralama yalnızca ESKİ format için yapılır:
    //   "ParçaAdı (ölçü) : Marka / Model1 / Model2"
    // Bunun işareti: PARANTEZ DIŞINDA bir ":" olması. Modern adlarda
    // (örn. "KUBOTA V4000 Segman Seti (STD - ÇAP: 105 mm) (15451-21050)")
    // ":" parantez içindedir; bu durumda sıraya DOKUNMA, sadece boşlukları düzelt.
    $colonPos = seo_find_top_level_colon($title);
    if ($colonPos === -1) {
        $clean = preg_replace('/\s+/u', ' ', $title);
        $clean = trim($clean);
        if ($ref !== '' && mb_stripos($clean, $ref) === false) {
            $clean .= ' (' . $ref . ')';
        }
        return $clean !== '' ? $clean : trim($title);
    }

    $left = trim(substr($title, 0, $colonPos));
    $rightSide = trim(substr($title, $colonPos + 1));

    // Sol taraftan parça adı ve (varsa) sondaki "(ölçü)" ayrıştır.
    $partName = $left;
    $size = '';
    if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $left, $lm)) {
        $partName = trim($lm[1]);
        $size = trim($lm[2]);
    }

    $models = [];
    $chunks = preg_split('/\s*\/\s*/u', $rightSide);
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '' || preg_match('/referans/i', $chunk)) {
            continue;
        }
        $models[] = $chunk;
    }

    $out = [];
    if (!empty($models)) {
        $brand = array_shift($models);
        $out[] = mb_strtoupper($brand, 'UTF-8');
        foreach ($models as $model) {
            $out[] = mb_strtoupper($model, 'UTF-8');
        }
    }

    if ($partName !== '') {
        $out[] = mb_convert_case(mb_strtolower($partName, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
    if ($size !== '') {
        $out[] = '(' . $size . ')';
    }
    if ($ref !== '') {
        $out[] = '(' . $ref . ')';
    }

    $result = trim(implode(' ', $out));
    return $result !== '' ? $result : trim($title);
}

/**
 * OpenAI chat completion (AI Asistan ile ayni API).
 */
function openai_chat_completion($apiKey, $model, $systemPrompt, $userMessage, $maxTokens = 300) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ],
        'temperature' => 0.3,
        'max_tokens' => $maxTokens,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => 'Baglanti hatasi: ' . $error];
    }
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'API hatasi';
        return ['success' => false, 'error' => $errorMsg];
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'message' => trim($result['choices'][0]['message']['content'])];
    }
    return ['success' => false, 'error' => 'Yanit alinamadi'];
}

/**
 * Urun adini AI ile SEO uyumlu hale getirir; API yoksa kural tabanli doner.
 */
function seo_optimize_product_title(PDO $db, $rawTitle, $stokKodu = '', $referans = '') {
    $rawTitle = trim((string)$rawTitle);
    if ($rawTitle === '') {
        return ['success' => false, 'error' => 'Urun adi bos'];
    }

    $context = "Mevcut urun adi: {$rawTitle}";
    if ($stokKodu !== '') {
        $context .= "\nStok kodu: {$stokKodu}";
    }
    if ($referans !== '') {
        $context .= "\nReferans no: {$referans}";
    }

    try {
        $aiSettings = $db->query('SELECT api_key, model FROM ai_ayar LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $aiSettings = false;
    }

    if ($aiSettings && !empty($aiSettings['api_key'])) {
        $systemPrompt = "Sen bir e-ticaret SEO uzmanisin. Yedek parca urun adlarini Google aramalari icin optimize ediyorsun.\n"
            . "Kurallar:\n"
            . "- Format: Marka Model1 Model2 ParcaAdi (olcu) (referans)\n"
            . "- Ornek: Kubota Z402 Z482 Piston Biyel Kolu\n"
            . "- Slash (/) kullanma, bosluk kullan\n"
            . "- Marka ve model kodlarini koru (KUBOTA, D1403, Z402 vb.)\n"
            . "- Referans numarasi varsa sona parantez icinde ekle\n"
            . "- Sadece tek satir urun adi don, aciklama yazma\n"
            . "- Tirnak veya markdown kullanma";

        $ai = openai_chat_completion(
            $aiSettings['api_key'],
            !empty($aiSettings['model']) ? $aiSettings['model'] : 'gpt-3.5-turbo',
            $systemPrompt,
            $context . "\n\nBu urun icin SEO uyumlu Turkce urun adi uret.",
            180
        );

        if (!empty($ai['success']) && !empty($ai['message'])) {
            $optimized = trim($ai['message'], " \t\n\r\0\x0B\"'");
            $optimized = preg_replace('/^urun adi\s*:\s*/iu', '', $optimized);
            if ($optimized !== '') {
                return [
                    'success' => true,
                    'title' => $optimized,
                    'method' => 'ai',
                ];
            }
        }
    }

    return [
        'success' => true,
        'title' => seo_optimize_title_rules($rawTitle),
        'method' => 'rules',
    ];
}

function cleanAZ($s) {
  $s = preg_replace('/([^a-zA-Z0-9-_]*)/i', '', $s);
  return $s;
}

function clean_string($s) {
    if ( is_array($s) ) {
        foreach ($s as $s_key=>$s_val) {
            $s[$s_key] = clean_string($s_val);
        }
    } else {
        if (
            ( function_exists("get_magic_quotes_gpc"))
            ||
            ( ini_get('magic_quotes_sybase') && strtolower(ini_get('magic_quotes_sybase'))!='off' )
        ) {
            $s = stripslashes($s);
        }
    }
    
    return $s;
}


function clean($s) {
    $s = is_array($s) ? array_map('clean', $s) : clean_string($s);
    return $s;
}

function parseJsonArray($jsonArray, $parentID = 0) {
  $return = array();
  foreach ($jsonArray as $subArray) {
    $returnSubSubArray = array();
    if (isset($subArray->children)) {
      $returnSubSubArray = parseJsonArray($subArray->children, $subArray->id);
    }
    $return[] = array('id' => $subArray->id, 'parentID' => $parentID);
    $return = array_merge($return, $returnSubSubArray);
  }
  return $return;
}


function Sayfala($top_sayfa,$page,$limit,$page_url){
    // Sayfalama Şeridimiz

    if ($top_sayfa > $limit) :


    $x = 5; // Aktif sayfadan önceki/sonraki sayfa gösterim sayisi
    $lastP = ceil($top_sayfa / $limit);

    // sayfa 1'i yazdir
    if ($page==1){
        echo '<li class="page-item active">
                <a class="page-link" href="#">1</a>
            </li>';
    }else{
        echo '<li class="page-item">
                <a class="page-link" href="'.$page_url.'1">1</a>
            </li>';
    }

    // "..." veya direkt 2
    if ($page-$x>2){
        echo '<li class="page-item">
                <a class="page-link" href="#">...</a>
            </li>';
        $i = $page-$x;
    }else{
        $i = 2;
    }
    // +/- $x sayfalari yazdir
    for ($i; $i<=$page+$x; $i++){
        if ($i==$page)
        echo '<li class="page-item active">
                <a class="page-link" href="#">'.$i.'</a>
            </li>';
        else
        echo '<li class="page-item">
                <a class="page-link" href="'.$page_url.''.$i.'">'.$i.'</a>
            </li>';
        if ($i==$lastP)
        break;
    }

    // "..." veya son sayfa
    if ($page+$x<$lastP-1){
        echo '<li class="page-item">
                <a class="page-link" href="#">...</a>
            </li>';
        echo '<li class="page-item">
                <a class="page-link" href="'.$page_url.''.$lastP.'">'.$lastP.'</a>
            </li>';
    }elseif ($page+$x==$lastP-1){
        echo '<li class="page-item">
                <a class="page-link" href="'.$page_url.''.$lastP.'">'.$lastP.'</a>
            </li>';
    }
    endif;
}


function alt_kategori_bul($x){
    global $db;
    $query = $db->query("SELECT * FROM kategori WHERE ust_kategori = '{$x}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
    if($query->rowCount()){
        $kataegori_idleri = $x.',';
        foreach($query as $row){
          $kataegori_idleri .= alt_kategori_bul($row['id']);
        }
    }else{
      $kataegori_idleri = $x.',';
    }
    return $kataegori_idleri;
}
function alt_kategori_bul1($x){
    global $db;
    $query = $db->query("SELECT * FROM tkategori WHERE ust_kategori = '{$x}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
    if($query->rowCount()){
        $kataegori_idleri = $x.',';
        foreach($query as $row){
          $kataegori_idleri .= alt_kategori_bul1($row['id']);
        }
    }else{
      $kataegori_idleri = $x.',';
    }
    return $kataegori_idleri;
}

function fiyat($deger){
    return number_format($deger, 2, ',', '.');
}


$siparis_durum[0] = 'Onay Bekliyor';
$siparis_durum[1] = 'Odeme Bekleniyor';
$siparis_durum[2] = 'Odeme Alındı';
$siparis_durum[3] = 'Onaylandı';
$siparis_durum[4] = 'Kargoda';
$siparis_durum[5] = 'Tamamlandı';
$siparis_durum[6] = 'İptal Edildi';




$yorum_durum[0] = 'Onay Bekliyor';
$yorum_durum[1] = 'Onaylandı';
$yorum_durum[2] = 'İptal Edildi';


$odeme_yontemi[1] = 'Online Kredi Kartı';
$odeme_yontemi[2] = 'Kapıda Kredi Kartı';
$odeme_yontemi[3] = 'Kapıda Nakit';
$odeme_yontemi[4] = 'Banka Havalesi';


$kredi_karti_odendi[0] = '<b style="color:red">Kredi Kartı Ödemesi Yapılmadı</b>';
$kredi_karti_odendi[1] = '<b style="color:green">Kredi Kartı Ödemesi Yapıldı</b>';

// Stok değerlerini clean() işleminden önce sakla - TÜM VARYANTLAR
$stok_backup = isset($_POST['stok']) ? $_POST['stok'] : null;
$stok_hidden_backup = isset($_POST['stok_hidden']) ? $_POST['stok_hidden'] : null;
$stok_force_backup = isset($_POST['stok_force']) ? $_POST['stok_force'] : null;

foreach($_GET    as $k => $v) $_GET[$k]    = clean($v);

// POST verilerini temizle - AMA STOK ALANLARINI ATLA
foreach($_POST   as $k => $v) {
    // Stok ile ilgili alanları clean() işleminden geçirme
    if($k === 'stok' || $k === 'stok_hidden' || $k === 'stok_force'){
        continue; // Skip clean() for stok fields
    }
    $_POST[$k] = clean($v);
}

// Stok değerlerini geri yükle (clean() işlemi bunları etkilemesin)
if($stok_backup !== null){
    $_POST['stok'] = $stok_backup;
}
if($stok_hidden_backup !== null){
    $_POST['stok_hidden'] = $stok_hidden_backup;
}
if($stok_force_backup !== null){
    $_POST['stok_force'] = $stok_force_backup;
}

function sms($text,$numara){
    global $db;
    $sms_ayari = $db->query("SELECT * FROM netgsm_ayari LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if(!empty($sms_ayari['username']) AND !empty($sms_ayari['password'])){
        try {
            $client = new SoapClient("http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl");
            $msg  = $text;
            $gsm  = array('9'.$numara);

            $Result = $client -> smsGonder1NV2(array('username'=>$sms_ayari['username'], 'password' => $sms_ayari['password'], 'header' => $sms_ayari['header'], 'msg' => $msg, 'gsm' => $gsm,  'filter' => '', 'startdate'  => '', 'stopdate'  => '', 'encoding' => ''  ));

        } catch (Exception $exc){
         echo "Soap Hatasi Olustu: " . $exc->getMessage();
        }
    }
}
?>