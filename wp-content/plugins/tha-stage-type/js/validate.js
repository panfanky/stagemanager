jQuery(document).ready(function() {		
                jQuery(document).on("click", ".editor-post-publish-button", function(e){
					// e.preventDefault(); // no effect
					var err="";
					if(jQuery("select[name=thatown]").val()=="")
						err += "Pozor, není nastaveno město\n";
					if(jQuery("input[name=thadate_datepart]").val()=="")
						err += "Pozor, není nastaven začátek události\n";
					if(jQuery("input[name=thaplace]").val()=="" && jQuery("input[name=thageo]").val()=="")
						err += "Pozor, není nastaveno místo ani GPS";
					if(err!="") {
						// err += "\n\n Událost nebude zveřejněna, pouze uložena jako koncept.";
						alert(err);
					}
                });
				
});