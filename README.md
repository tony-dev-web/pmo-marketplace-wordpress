# PieceMotoOccasion marketplace pour WordPress

Vendez vos pièces moto sur la marketplace française [PieceMotoOccasion](https://piecemotooccasion.eu) depuis votre site WordPress (avec WooCommerce, l'extension de boutique gratuite de WordPress).

- Envoi du catalogue (produits publiés avec un SKU), puis envoi automatique à chaque modification.
- Stock synchronisé à chaque changement de quantité, synchronisation complète quotidienne.
- Chaque commande payée sur PieceMotoOccasion crée une commande WooCommerce (statut « En cours »).
- Numéro de suivi transmis quand la commande passe « Terminée ».

**Page et guide d'installation** : https://piecemotooccasion.eu/extensions/wordpress
**Téléchargement** : https://cdn.piecemotooccasion.eu/static/plugins/pmo-marketplace.zip
**Jeton API** : espace vendeur, page « Ma boutique en ligne »

## Installation

1. Si le site n'a pas WooCommerce : Extensions › Ajouter, chercher « WooCommerce », installer et activer. Saisir les pièces avec un SKU (la référence).<br>2. Extensions › Ajouter › Téléverser le zip, puis activer.
3. WooCommerce › PieceMotoOccasion : jeton API (espace vendeur) et catégorie par défaut ; la première catégorie WooCommerce du produit est envoyée quand elle existe.
4. Copier l'URL de notification affichée (`.../wp-json/piecemoto/v1/commande`) dans votre espace vendeur.
5. « Envoyer tout le catalogue » : chaque nouvelle pièce est vérifiée avant sa mise en ligne.

## API

L'extension utilise l'API vendeur PieceMotoOccasion (`https://piecemotooccasion.eu/api/v1`, jeton Bearer généré dans l'espace vendeur) : `PUT /produits`, `PATCH /produits/<ref>/stock`, `DELETE /produits/<ref>`, `GET /commandes`, `POST /commandes/<id>/expedier`, et reçoit un webhook JSON signé HMAC-SHA256 (`X-Pmo-Signature`) à chaque commande payée.

Licence GPL-2.0-or-later.

## La plateforme

Cette extension s'installe sur WordPress, qui n'est pas edite par PieceMotoOccasion.

- Site officiel : https://wordpress.org
- Code source de la plateforme : https://github.com/WordPress/WordPress
- Documentation pour developpeurs : https://developer.wordpress.org/plugins/

## Les extensions PieceMotoOccasion

- [PrestaShop](https://github.com/tony-dev-web/pmo-marketplace-prestashop)
- [WooCommerce](https://github.com/tony-dev-web/pmo-marketplace-woocommerce)
- [Shopify](https://github.com/tony-dev-web/pmo-marketplace-shopify)
- [Drupal](https://github.com/tony-dev-web/pmo-marketplace-drupal)
- [Magento](https://github.com/tony-dev-web/pmo-marketplace-magento)
- [API](https://github.com/tony-dev-web/pmo-marketplace-api)
- Toutes les extensions : https://piecemotooccasion.eu/extensions/
