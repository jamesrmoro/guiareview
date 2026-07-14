jQuery(document).ready(function($) {
    // Função para obter o modelo do aparelho
    function getMobileModel() {
        var userAgent = navigator.userAgent;
        var modelo = "Desconhecido";

        // Detectando marcas e modelos populares (Samsung, iPhone, LG, Huawei, etc)
        var devices = {
            'Samsung': /Samsung/i,
            'iPhone': /iPhone/i,
            'iPad': /iPad/i,
            'iPod': /iPod/i,
            'LG': /LG/i,
            'Huawei': /Huawei/i,
            'Google': /Pixel/i,
            'Motorola': /Motorola/i,
            'HTC': /HTC/i,
            'OnePlus': /OnePlus/i,
            'Nokia': /Nokia/i,
            'Sony': /Sony/i
        };

        // Iterar sobre as marcas e verificar se há correspondência
        for (var device in devices) {
            if (userAgent.match(devices[device])) {
                modelo = device;  // Marca detectada
                break;
            }
        }

        // Caso não tenha encontrado uma correspondência específica, retornar o userAgent completo
        if (modelo === "Desconhecido") {
            modelo = userAgent.match(/(Android|iPhone|iPad|iPod|Windows Phone);?[\s/]+(\S+)?/);
            if (modelo) {
                modelo = modelo[0];  // Captura o modelo genérico
            }
        }

        return modelo;
    }

    $.get('https://ipinfo.io/json', function(data) {
        var cidade = data.city || 'Desconhecida';
        var aparelho = getMobileModel();  // Captura o modelo do dispositivo

        // Quando o botão de download for clicado
        $('#installButton').on('click', function() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'enviar_email_download',
                    cidade: cidade,
                    aparelho: aparelho,
                    _ajax_nonce: meu_objeto_ajax.ajax_nonce  // Incluindo o nonce
                },
                success: function(response) {
                    if(response.success) {
                        console.log("Email enviado com sucesso.");
                    } else {
                        console.log("Erro ao enviar o email.");
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Erro AJAX:", error);
                }
            });
        });
    }).fail(function() {
        console.log("Erro ao obter a localização.");
    });
});
