# TaloPay Transferencias para Magento 2

<div>
    <a href="https://www.loom.com/share/e430904662b440c48c5804761d1c8a75">
      <p>Magento DEMO - ver video</p>
    </a>
    <a href="https://www.loom.com/share/e430904662b440c48c5804761d1c8a75">
      <img style="max-width:600px;" src="https://cdn.loom.com/sessions/thumbnails/e430904662b440c48c5804761d1c8a75-debf02ffdf2dac47-full-play.gif">
    </a>
  </div>

## Descripción

TaloPay Transfer es un módulo de Magento 2 que proporciona una solución de pagos integrada para tu tienda online. Este módulo permite procesar transferencias bancarias de manera segura y eficiente.

## Requisitos

-   Magento CE/EE 2.x
-   PHP 7.4 o superior
-   Composer

## Instalación

### Vía Composer (Recomendado)

```bash
composer require talopay/transfer
php bin/magento module:enable TaloPay_Transfer
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

### Instalación Manual

1. Descarga el módulo
2. Descomprime el archivo y copia el contenido en la carpeta `app/code/TaloPay/Transfer` de tu instalación de Magento
3. Ejecuta los siguientes comandos:

```bash
php bin/magento module:enable TaloPay_Transfer
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

## Configuración

1. Accedé al panel de administración de Magento
2. Ve a Stores > Configuration > Sales > Payment Methods
3. Buscá la sección "TaloPay Transfer"
4. Configurá las credenciales que podés obtener en [tu panel de administración de TaloPay](https://app.talo.com.ar/payment-methods/transfer/plugins/magento)

## Características

-   Procesamiento automático de transferencias bancarias
-   Panel de administración intuitivo
-   Integración perfecta con el flujo de checkout de Magento
-   Registro detallado de transacciones
-   Notificaciones automáticas de estado de pago

## Soporte

Para obtener soporte técnico o reportar problemas, por favor:

-   Abrí un issue en nuestro repositorio
-   Contactá a nuestro equipo de soporte en [whatsapp](https://wa.me/5491133711752)
-   Consultá nuestra [documentación en línea](https://docs.talo.com.ar/)

## Licencia

Este módulo está licenciado bajo OSL-3.0 y AFL-3.0.

## Versión

Versión actual: 1.0.0
