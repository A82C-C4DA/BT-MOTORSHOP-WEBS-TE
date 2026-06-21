<!DOCTYPE HTML>
    <html>
    <head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fancy Product Designer</title>

    <!-- Style sheets -->
    <link rel="stylesheet" type="text/css" href="css/main.css">

    <!-- The CSS for the plugin itself - required -->
	<link rel="stylesheet" type="text/css" href="css/FancyProductDesigner-all.min.css" />

    <!-- Include required jQuery files -->
	<script src="js/jquery.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui.min.js" type="text/javascript"></script>

	<!-- HTML5 canvas library - required -->
	<script src="js/fabric.min.js" type="text/javascript"></script>
	<!-- The plugin itself - required -->
	<script src="js/FancyProductDesigner-all.min.js" type="text/javascript"></script>

    <script type="text/javascript">
	    jQuery(document).ready(function(){

	    	var $yourDesigner = $('#clothing-designer'),
	    		pluginOpts = {
	    			<?php if(empty($_GET['id'])){ ?>
		    		productsJSON: 'json/ikili.json', //see JSON folder for products sorted in categories
		    		<?php } ?>
		    		designsJSON: 'json/designs.json', //see JSON folder for designs sorted in categories
		    		stageWidth: 1920,
		    		stageHeight: 1200,
		    		editorMode: false,
		    		smartGuides: true,
		    		//uiTheme: 'doyle',
		    		fonts: [
				    	{name: 'Helvetica'},
				    	{name: 'Times New Roman'},
				    	{name: 'Arial'},
			    		{name: 'Lobster', url: 'google'}
			    	],
		    		customTextParameters: {
			    		colors: true,
			    		removable: true,
			    		resizable: true,
			    		draggable: true,
			    		rotatable: true,
			    		autoCenter: true,
			    		boundingBox: "Base",
			    		curvable: true
			    	},
		    		customImageParameters: {
			    		draggable: true,
			    		removable: true,
			    		resizable: true,
			    		rotatable: true,
			    		colors: '#000',
			    		autoCenter: true,
			    		boundingBox: "Base"
			    	},
			    	actions:  {
						'top': ['download','print', 'snap', 'preview-lightbox'],
						'right': ['magnify-glass', 'zoom', 'reset-product', 'qr-code', 'ruler'],
						'bottom': ['undo','redo'],
						'left': ['manage-layers','info','save','load']
					}
	    		},

	    	yourDesigner = new FancyProductDesigner($yourDesigner, pluginOpts);

	    	
			$('#store-product-db').click(function() {
			    //Send data (action and product views) to PHP script
			    $.post("olustur.php", { action: 'store', json_adi: '<?php echo $_GET['json_adi']; ?>', views: JSON.stringify(yourDesigner.getProduct()) }, function(data) {
			        //check for errors
			        if(parseInt(data) > 0) {
			            //successfully added
			            alert('Şablon Oluşturuldu. Güncelle Butonuna Basın.');
			        }
			        else {
			            alert('Error: '+data+'');
			        }
			    });
			});


			
		       $.post("olustur.php", { action: 'load', json_adi: '<?php echo $_GET['json_adi']; ?>' }, function(data) {
					if(data != 0){
						yourDesigner.loadProduct(JSON.parse(data));
					}
			    });


	    });
    </script>
    </head>

    <body>

		<center><a href="#" id="store-product-db" class="fpd-btn" style="padding: 20px;background: #078207;color: #fff;text-decoration: none;display: inline-block;border-radius: 10px;">Şablonu Oluştur</a></center>
	
    	<div id="main-container">
          	<div id="clothing-designer" class="fpd-container fpd-shadow-2 fpd-topbar fpd-tabs fpd-tabs-side fpd-top-actions-centered fpd-bottom-actions-centered fpd-views-inside-left"> </div>
    	</div>
    </body>
</html>