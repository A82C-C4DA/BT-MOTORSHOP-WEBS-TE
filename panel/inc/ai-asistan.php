<?php
// AI Asistan Sayfası
?>
<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<div>
		  <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">AI Asistan</h2>
		  <p class="mg-b-0">Yapay zeka ile admin panel işlemlerinizi kolaylaştırın!</p>
		</div>
	</div>
</div>
<!-- /breadcrumb -->

<div class="row">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">AI Asistan ile Konuşun</h3>
				<div class="card-options">
					<button type="button" class="btn btn-sm btn-primary" id="clearChat">
						<i class="fe fe-trash-2"></i> Sohbeti Temizle
					</button>
				</div>
			</div>
			<div class="card-body">
				<div id="aiChatContainer" style="height: 500px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 5px; padding: 15px; background: #f9f9f9; margin-bottom: 15px;">
					<div class="chat-message ai-message" style="background: #e3f2fd; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
						<strong>AI Asistan:</strong> Merhaba! Size nasıl yardımcı olabilirim? Ürün açıklamaları oluşturabilir, satış verilerini analiz edebilir, içerik üretebilir ve sorularınızı yanıtlayabilirim.
					</div>
				</div>
				<div class="input-group">
					<input type="text" class="form-control" id="aiMessageInput" placeholder="Mesajınızı yazın... (Örn: Bugünkü satışları analiz et, Ürün açıklaması oluştur, vb.)" autocomplete="off">
					<div class="input-group-append">
						<button class="btn btn-primary" type="button" id="sendAiMessage">
							<i class="fe fe-send"></i> Gönder
						</button>
					</div>
				</div>
				<div class="mt-3">
					<small class="text-muted">
						<strong>Hızlı Komutlar:</strong>
						<button class="btn btn-sm btn-outline-primary ml-2 quick-command" data-command="Bugünkü satışları analiz et">📊 Satış Analizi</button>
						<button class="btn btn-sm btn-outline-primary ml-2 quick-command" data-command="Ürün açıklaması oluştur">📝 Ürün Açıklaması</button>
						<button class="btn btn-sm btn-outline-primary ml-2 quick-command" data-command="Blog yazısı başlıkları öner">✍️ Blog Başlıkları</button>
						<button class="btn btn-sm btn-outline-primary ml-2 quick-command" data-command="Müşteri yorumlarını analiz et">💬 Yorum Analizi</button>
						<button class="btn btn-sm btn-outline-success ml-2 quick-command" data-command="Otomobil yedek parçaları için bir kategori oluştur">📁 Kategori Oluştur</button>
						<button class="btn btn-sm btn-outline-info ml-2 quick-command" data-command="Kategorileri mantıklı bir sıraya göre düzenle">🔄 Kategorileri Sırala</button>
					</small>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.chat-message {
	margin-bottom: 15px;
	padding: 12px;
	border-radius: 8px;
	animation: fadeIn 0.3s;
}

.user-message {
	background: #007bff;
	color: white;
	margin-left: 20%;
	text-align: right;
}

.ai-message {
	background: #e3f2fd;
	color: #333;
	margin-right: 20%;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
}

.typing-indicator {
	display: inline-block;
}

.typing-indicator span {
	display: inline-block;
	width: 8px;
	height: 8px;
	border-radius: 50%;
	background: #666;
	margin: 0 2px;
	animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
	animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
	animation-delay: 0.4s;
}

@keyframes typing {
	0%, 60%, 100% { transform: translateY(0); }
	30% { transform: translateY(-10px); }
}
</style>

<script>
$(document).ready(function() {
	const chatContainer = $('#aiChatContainer');
	const messageInput = $('#aiMessageInput');
	const sendButton = $('#sendAiMessage');
	const clearButton = $('#clearChat');

	// Mesaj gönderme
	function sendMessage() {
		const message = messageInput.val().trim();
		if (!message) return;

		// Kullanıcı mesajını göster
		addMessage(message, 'user');
		messageInput.val('');
		
		// Typing indicator göster
		showTypingIndicator();

		// AI'ye gönder
		$.ajax({
			url: 'inc/ai-handler.php',
			method: 'POST',
			data: {
				message: message,
				action: 'chat'
			},
			dataType: 'json',
			success: function(response) {
				hideTypingIndicator();
				if (response.success) {
					addMessage(response.message, 'ai');
				} else {
					addMessage('Üzgünüm, bir hata oluştu: ' + (response.error || 'Bilinmeyen hata'), 'ai');
				}
			},
			error: function() {
				hideTypingIndicator();
				addMessage('Bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'ai');
			}
		});
	}

	// Mesaj ekleme
	function addMessage(text, type) {
		const messageClass = type === 'user' ? 'user-message' : 'ai-message';
		const sender = type === 'user' ? 'Siz' : 'AI Asistan';
		const messageHtml = `
			<div class="chat-message ${messageClass}">
				<strong>${sender}:</strong> ${escapeHtml(text)}
			</div>
		`;
		chatContainer.append(messageHtml);
		chatContainer.scrollTop(chatContainer[0].scrollHeight);
	}

	// Typing indicator
	function showTypingIndicator() {
		const typingHtml = `
			<div class="chat-message ai-message typing-indicator" id="typingIndicator">
				<strong>AI Asistan:</strong> 
				<span></span><span></span><span></span>
			</div>
		`;
		chatContainer.append(typingHtml);
		chatContainer.scrollTop(chatContainer[0].scrollHeight);
	}

	function hideTypingIndicator() {
		$('#typingIndicator').remove();
	}

	// HTML escape
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Event listeners
	sendButton.on('click', sendMessage);
	messageInput.on('keypress', function(e) {
		if (e.which === 13) {
			sendMessage();
		}
	});

	clearButton.on('click', function() {
		if (confirm('Sohbet geçmişini temizlemek istediğinize emin misiniz?')) {
			chatContainer.html(`
				<div class="chat-message ai-message">
					<strong>AI Asistan:</strong> Merhaba! Size nasıl yardımcı olabilirim? Ürün açıklamaları oluşturabilir, satış verilerini analiz edebilir, içerik üretebilir ve sorularınızı yanıtlayabilirim.
				</div>
			`);
		}
	});

	// Hızlı komutlar
	$('.quick-command').on('click', function() {
		const command = $(this).data('command');
		messageInput.val(command);
		sendMessage();
	});
});
</script>

