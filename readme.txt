=== PieceMotoOccasion Marketplace ===
Contributors: piecemomotooccasion
Tags: marketplace, pmo, motorcycle parts, pieces moto, casse moto
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://piecemotooccasion.eu/extensions/woocommerce
Author URI: https://piecemotooccasion.eu

Sell your products on the PieceMotoOccasion marketplace: catalogue and stock kept in sync, PieceMotoOccasion orders created in your shop.

== Description ==
PieceMotoOccasion Marketplace connects your WordPress shop to PieceMotoOccasion, the French marketplace for used motorcycle parts.

* Sends your catalogue (published products with a SKU), then every product change automatically.
* Keeps stock in sync on every quantity change, with a full daily synchronisation.
* Every order paid on PieceMotoOccasion creates an order in your shop (status "Processing", note "PieceMotoOccasion order #...").
* Sends the tracking number back to PieceMotoOccasion when the order is completed.

You need an PieceMotoOccasion seller account with an active Stripe payment account. New products are reviewed by PieceMotoOccasion before going live.

== External services ==
This plugin communicates with the PieceMotoOccasion API (https://piecemotooccasion.eu/api/v1). It sends your products (name, description, price, stock, image URLs) and reads your orders; PieceMotoOccasion sends a signed notification to your site for every paid order. The API token is generated in your PieceMotoOccasion seller area. Terms: https://piecemotooccasion.eu/cgv/ - Privacy: https://piecemotooccasion.eu/mentions-legales

== Links ==
* Plugin page and guide: https://piecemotooccasion.eu/extensions/woocommerce
* All extensions (PrestaShop, Shopify): https://piecemotooccasion.eu/extensions/
* Become a seller: https://piecemotooccasion.eu/a2/vendeurs/formulaire
* Seller area (API token, orders, payouts): https://piecemotooccasion.eu/a2/vendeurs/boutique
* Marketplace: https://piecemotooccasion.eu

== Installation ==
1. Plugins > Add New > Upload Plugin, then activate.
2. Open your shop menu > PieceMotoOccasion: paste the API token generated in your PieceMotoOccasion seller area (https://piecemotooccasion.eu/a2/vendeurs/boutique) and choose the PieceMotoOccasion category.
3. Copy the notification URL shown by the plugin (.../wp-json/piecemoto/v1/commande) into your PieceMotoOccasion seller area.
4. Click "Send the whole catalogue". PieceMotoOccasion reviews new products before they go live.

== Frequently Asked Questions ==
= Are my prices changed? =
No. PieceMotoOccasion receives your tax-included price and publishes it as is; the PieceMotoOccasion commission is deducted from your payout.

= Do PieceMotoOccasion orders appear in my shop statistics? =
Yes, they are regular orders created via "piecemotooccasion", with the PieceMotoOccasion order number in the note.

== Changelog ==

= 1.0.1 =
* Prefixes renamed from pmo_ to piecemoto_ as required by the plugin review. Settings are re-entered after the update.
* Notification route moved to /wp-json/piecemoto/v1/commande.
* Capability check added on the settings screen.
= 1.0.0 =
* First release.
