DataAccess
==========
Este módulo tem por objetivo fornecer acesso a dados à diferentes fontes de tal forma que a utilização seja indiferente à fonte.

Utilização
---------
No arquivo composer.json devem ser adicionados as seguintes informações

    "repositories": [
        {
            "type": "vcs",
            "url": "http://10.1.1.10:8080/absoluta/abscore-data-access.git"
        }
    ]

e

    "require": {
        "ABSCore/DataAccess": "dev-master"
    }


No arquivo config/application.config.php deve ser adicionada a entrada para módulo

    return array(
        'modules' => array(
            'Application',
            'ABSCore\DataAccess', // Esta é a entrada para o módulo
        ),
        'module_listener_options' => array(
            'module_paths' => array(
                './module',
                './vendor'
            ),
            'config_glob_paths' => array(
                'config/autoload/{,*.}{global,local}.php'
            )
        )
    );
