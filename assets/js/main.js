Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
    var n = this,
        decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
        decSeparator = decSeparator == undefined ? "." : decSeparator,
        thouSeparator = thouSeparator == undefined ? "," : thouSeparator,
        sign = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
};
function sepet(){
  $.ajax({
      url: "post.php",
      type: "post",
      data: 'islem=listele',
      success: function (x) {
        $('#sepet').html(x);
      }
  });
}
function sepet_sayisi(){
  $.ajax({
      url: "post.php",
      type: "post",
      data: 'islem=sepet_sayisi',
      success: function (x) {
        $('[data-sepetsayisi=""]').html(x);
      }
  });
}

$(function(){
	$('[data-toggle="tooltip"]').tooltip();

    sepet();

    $('#renk').change(function(){
      	window.location.href=$(this).val();
    });
    $('#subscribeButton').click(function(){
            $.ajax({
                url: "post.php",
                type: "post",
                data: 'islem=abone&email='+$('#abonemail').val(),
                success: function (x) {
                  if(x == 1){
                    alert('Başarı ile kayıt oldunuz.');
                  }else{
                    alert('Bu email adresi kullanılıyor.');
                  }
                }
            });
    });
	  
    $('.varyant ul li').click(function(){
        $('.varyant ul li').removeClass('aktif');
        $(this).addClass('aktif');

        var fiyat = ((parseInt($('[data-guncel-fiyat]').attr('data-guncel-fiyat'))) + parseInt($(this).attr('data-fiyat'))).formatMoney(2,'.',',');
        $('[data-guncel-fiyat]').html( fiyat + ' TL');
    });

    $(document).on('click','#saydam_bg, [data-sepet-kapat=""]',function(){
        $('#saydam_bg').fadeOut(500);
        $('#sepet').fadeOut(500);
    });

    $('[data-sepete-ekle]').click(function(){
        
        $('#sepete_ekle_durum').removeClass().html('');
        var devam = 0;
        var secenek_id = 0;

        if(parseInt($('[name="adet"]').val()) < 1){
          $('#sepete_ekle_durum').addClass('hata').html('Lütfen geçerli bir adet giriniz.');
        }else{
            if($('.varyant li').length){
              if($('.varyant li').hasClass('aktif')){
                // Stok kontrolü: stok = 1 ise (Var) sınırsız, stok = 0 ise (Yok) eklenemez
                var stok_durumu = parseInt($('[data-stok].aktif').attr('data-stok'));
                if(stok_durumu == 0){
                  $('#sepete_ekle_durum').addClass('hata').html('Bu seçenek stokta yok.');
                }else{
                  secenek_id =  $('[data-stok].aktif').attr('data-secenek-id');
                  devam = 1;
                }
              }else{
                $('#sepete_ekle_durum').addClass('hata').html('Lütfen bir seçenek seçiniz.');
              }
            }else{
              devam = 1;
            }
        }
        var islem = 'urun-ekle';
        if($('[name="img"]').val()){
         islem = 'turun-ekle';
        }
      
      	var json = '';
      	if($('[name="json"]').val()){
        	json = $('[name="json"]').val();
        }
      	var img = '';
      	if($('[name="img"]').val()){
        	img = $('[name="img"]').val();
        }

      if(devam == 1){
          $.ajax({
              url: "post.php",
              type: "post",
              data: 'islem='+islem+'&urun_id='+parseInt($(this).attr('data-sepete-ekle'))+'&adet='+parseInt($('[name="adet"]').val())+'&secenek_id='+secenek_id+'&json='+json+'&img='+img,
              success: function (x) {
                  if(x == 0){
                    $('#sepete_ekle_durum').addClass('hata').html('Yeterli stok bulunamadı.');
                  }else if(x == 1){
                    $('#sepete_ekle_durum').addClass('hata').html('Geçersiz istek.');
                  }else{
                    $('#sepete_ekle_durum').addClass('basari').html('Ürün başarı ile sepete eklendi.');
                    sepet();
                    $('#saydam_bg').fadeIn(500);
                    $('#sepet').fadeIn(500);
                    sepet_sayisi();
                  }
              }
          });
      }

      $('#sepete_ekle_durum').fadeIn(500);

    });

    $(document).on('click','[data-sepet-sil]',function(){
        $.ajax({
              url: "post.php",
              type: "post",
              data: 'islem=sepet_sil&id='+$(this).attr('data-sepet-sil'),
              success: function (x) {
                sepet();
                sepet_sayisi();
              }
        });
    });

    $(document).on('click','[data-sepet-sayfa-sil]',function(){
        $.ajax({
              url: "post.php",
              type: "post",
              data: 'islem=sepet_sil&id='+$(this).attr('data-sepet-sayfa-sil'),
              success: function (x) {
               location.reload();
              }
        });
    });

    

    $('[data-favori-ekle]').click(function(){
        $.ajax({
          url: "post.php",
          type: "post",
          data: 'islem=favori-ekle&urun_id='+$(this).attr('data-favori-ekle'),
          success: function (x) {
            $('#modal_dis .modal-body').html('<center><img src="assets/images/basari.png" style="width: 150px" class="img-responsive"><br><h3>Ürün Başarı ile Favorilere Eklendi</h3></center>');
            $('#modal_dis .modal-footer a').html('Favorileri Ürünlerimi Göster');
            $('#modal_dis .modal-footer a').attr('href','favorilerim');
            $('#modal_dis').modal('show');
          }
        });
    });

    $('[data-favori-sil]').click(function(){
        $.ajax({
          url: "post.php",
          type: "post",
          data: 'islem=favori-sil&urun_id='+$(this).attr('data-favori-sil'),
          success: function (x) {
            location.reload();
          }
        });
    });

     $('[data-sepet-ac]').click(function(){
        $('#saydam_bg').fadeIn(500);
        $('#sepet').fadeIn(500);
    });
    
    
    $('.arti').click(function(){
        var adet = parseInt($('#adet').val());
        $('#adet').val(adet + 1);
    });

    $('.eksi').click(function(){
        var adet = parseInt($('#adet').val());
        if(adet > 1){
          $('#adet').val(adet - 1);
        }
    });

    $('#hikaye div a').click(function(){
        if($(this).attr('data-buyuk-img') == ''){
            window.location.href = $(this).attr('data-link');
        }else{

            $('#hikaye-popup #icerik').html('<a href="'+$(this).attr('data-link')+'"><img src="upload/'+$(this).attr('data-buyuk-img')+'" class="img-responsive"></a>');
            $('#hikaye-popup').modal('show');

            $('#saniye').animate({
              width: '100%'
            }, 5000, function() {
              $('#hikaye-popup').modal('hide');
              $('#saniye').css('width','0px');
            });

        }
    });

});