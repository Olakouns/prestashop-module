document.addEventListener('DOMContentLoaded', function () {
    if (window.location.href.indexOf('/scanpage') > -1) {
        Quagga.init({
            inputStream: {
              name: "Live",
              type: "LiveStream",
              target: document.querySelector('#scanner-container'),
              constraints: {
                width: 640,
                height: 480,
                facingMode: "environment",
              },
            },
            locator: {
              patchSize: "medium",
              halfSample: true,
            },
            numOfWorkers: navigator.hardwareConcurrency || 4,
            decoder: {
              readers: ["code_128_reader"],
            },
            locate: true,
          }, function (err) {
            if (err) {
                alert(err);
                console.log(err);
                return;
            }
            Quagga.start();

            console.log("ci");
        });
        
        var isProcessing = false; 

        Quagga.onDetected(function (result) {

            // Vérifier si le traitement est en cours
            if (isProcessing) {
                return;  // Si c'est le cas, ne pas effectuer le traitement
            }

            // Mettre la variable en mode de traitement
            isProcessing = true;

            var barcode = result.codeResult.code;
            //alert('Code-barres détecté : ' + barcode);
            console.log(barcode);

            $.ajax({
                url: prestashop.urls.pages.cart,
                type: 'POST',
                data: {
                    id_product: 7,  // Remplacez barcode par l'ID du produit extrait du code-barres
                    qty: 1,
                    token: prestashop.static_token,
                    add: 1,
                    action: "update"
                },
                success: function (response) {
                    console.log("ADD TO CART");
                    // console.log(response);
                    window.location.href = prestashop.urls.pages.cart;
                   // isProcessing = false;
                },
                error: function (error) {
                    console.error("Erreur lors de l'ajout du produit au panier : ", error);
                    isProcessing = false;
                }
            });
        });
    }
});
