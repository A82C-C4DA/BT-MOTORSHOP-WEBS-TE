<?php
// AI Asistan Otomatik Kurulum Scripti
// Bu dosyayı tarayıcıdan çalıştırarak veritabanı tablosunu otomatik oluşturabilirsiniz

include 'db-ayar.php';
include 'fonksiyon.php';

if(!isset($_SESSION['admin']['login'])){
    die('Yetkisiz erişim. Lütfen admin paneline giriş yapın.');
}

$message = '';
$error = '';

if(isset($_POST['kurulum'])){
    try {
        // Tabloyu oluştur
        $sql = "CREATE TABLE IF NOT EXISTS `ai_ayar` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `api_key` text NOT NULL,
          `model` varchar(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        $message = '✅ Veritabanı tablosu başarıyla oluşturuldu!';
        
    } catch(PDOException $e) {
        $error = '❌ Hata: ' . $e->getMessage();
    }
}

// Tablo var mı kontrol et
$tableExists = false;
try {
    $result = $db->query("SHOW TABLES LIKE 'ai_ayar'");
    $tableExists = $result->rowCount() > 0;
} catch(PDOException $e) {
    $tableExists = false;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Asistan Kurulum</title>
    <link href="inc/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 AI Asistan Kurulum</h1>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <h2>Kurulum Durumu</h2>
        
        <div class="status <?php echo $tableExists ? 'ok' : 'error'; ?>">
            <?php if($tableExists): ?>
                ✅ Veritabanı tablosu mevcut
            <?php else: ?>
                ❌ Veritabanı tablosu bulunamadı
            <?php endif; ?>
        </div>
        
        <?php if(!$tableExists): ?>
            <form method="POST">
                <p>Veritabanı tablosunu oluşturmak için aşağıdaki butona tıklayın:</p>
                <button type="submit" name="kurulum" class="btn btn-success">Veritabanı Tablosunu Oluştur</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>Kurulum Tamamlandı!</strong><br>
                Artık AI Asistan ayarlarını yapılandırabilirsiniz.
            </div>
            <a href="index.php?sayfa=ai-ayar" class="btn">AI Ayarlarına Git</a>
            <a href="index.php?sayfa=ai-asistan" class="btn">AI Asistanı Kullan</a>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>Sonraki Adımlar</h3>
        <ol>
            <li>✅ Veritabanı tablosunu oluşturun (yukarıdaki buton ile)</li>
            <li>🔑 <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API anahtarı</a> alın</li>
            <li>⚙️ <a href="index.php?sayfa=ai-ayar">AI Ayarları</a> sayfasından API anahtarınızı girin</li>
            <li>🚀 <a href="index.php?sayfa=ai-asistan">AI Asistan</a> sayfasından kullanmaya başlayın!</li>
        </ol>
        
        <div class="alert alert-info" style="margin-top: 20px;">
            <strong>Not:</strong> Bu kurulum scripti sadece veritabanı tablosunu oluşturur. 
            OpenAI API anahtarınızı <a href="index.php?sayfa=ai-ayar">AI Ayarları</a> sayfasından yapılandırmanız gerekir.
        </div>
    </div>
</body>
</html>

