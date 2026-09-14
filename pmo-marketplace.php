<?php
/**
 * Plugin Name: PieceMotoOccasion Marketplace
 * Plugin URI:  https://piecemotooccasion.eu/extensions/woocommerce
 * Description: Vendez vos produits sur la marketplace PieceMotoOccasion (compatible avec la boutique WordPress) : envoi du catalogue, stock synchronise, commandes PieceMotoOccasion creees dans WooCommerce.
 * Version:     1.0.1
 * Author:      PieceMotoOccasion
 * Author URI:  https://piecemotooccasion.eu
 * License:     GPL-2.0-or-later
 * Text Domain: piecemotooccasion-marketplace
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PIECEMOTO_API', 'https://piecemotooccasion.eu/api/v1');
define('PIECEMOTO_LOT', 50);

// ---------------------------------------------------------------- reglages

add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'PieceMotoOccasion', 'PieceMotoOccasion', 'manage_woocommerce', 'piecemoto', 'piecemoto_page_reglages');
});

add_action('admin_init', function () {
    register_setting('piecemoto', 'piecemoto_jeton', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('piecemoto', 'piecemoto_categorie', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('piecemoto', 'piecemoto_creer_commandes', ['sanitize_callback' => 'absint']);
});

function piecemoto_page_reglages() {
    // La capacite est deja exigee par add_submenu_page, mais la fonction doit
    // pouvoir etre appelee sans danger depuis n'importe ou.
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('Vous n\'avez pas les droits pour gerer cette page.', 'piecemotooccasion-marketplace'));
    }
    if (isset($_POST['piecemoto_synchroniser']) && check_admin_referer('piecemoto_sync')) {
        $bilan = piecemoto_synchroniser_tout();
        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($bilan));
    }
    ?>
    <div class="wrap">
      <h1>PieceMotoOccasion</h1>
      <form method="post" action="options.php">
        <?php settings_fields('piecemoto'); ?>
        <table class="form-table">
          <tr><th><label for="piecemoto_jeton">Jeton API PieceMotoOccasion</label></th>
              <td><input type="text" id="piecemoto_jeton" name="piecemoto_jeton" class="regular-text" value="<?php echo esc_attr(get_option('piecemoto_jeton')); ?>">
                  <p class="description">Genere dans votre espace vendeur : https://piecemotooccasion.eu/a2/vendeurs/boutique</p></td></tr>
          <tr><th><label for="piecemoto_categorie">Categorie par defaut</label></th>
              <td><input type="text" id="piecemoto_categorie" name="piecemoto_categorie" class="regular-text" value="<?php echo esc_attr(get_option('piecemoto_categorie', 'Pièce moto')); ?>">
                  <p class="description">Utilisee quand un produit n'a pas de categorie WooCommerce : Carénage, Selle, Jante… Sinon c'est sa premiere categorie WooCommerce qui part.</p></td></tr>
          <tr><th>Commandes PieceMotoOccasion</th>
              <td><label><input type="checkbox" name="piecemoto_creer_commandes" value="1" <?php checked(get_option('piecemoto_creer_commandes', 1), 1); ?>> Creer une commande WooCommerce a chaque commande payee sur PieceMotoOccasion</label>
                  <p class="description">Renseignez cette URL dans votre espace vendeur PieceMotoOccasion (notification des commandes) : <code><?php echo esc_url(rest_url('piecemoto/v1/commande')); ?></code></p></td></tr>
        </table>
        <?php submit_button('Enregistrer'); ?>
      </form>
      <form method="post">
        <?php wp_nonce_field('piecemoto_sync'); ?>
        <p><button class="button button-primary" name="piecemoto_synchroniser" value="1">Envoyer tout le catalogue a PieceMotoOccasion maintenant</button>
           <span class="description">Les produits publies avec un SKU sont envoyes ; les nouveaux sont valides par PieceMotoOccasion avant mise en ligne. Ensuite, chaque modification et chaque changement de stock sont envoyes automatiquement.</span></p>
      </form>
    </div>
    <?php
}

// ---------------------------------------------------------------- appels API

function piecemoto_appel($methode, $chemin, $corps = null) {
    $jeton = get_option('piecemoto_jeton');
    if (!$jeton) {
        return new WP_Error('piecemoto', 'jeton API manquant');
    }
    $reponse = wp_remote_request(PIECEMOTO_API . $chemin, [
        'method'  => $methode,
        'timeout' => 60,
        'headers' => ['Authorization' => 'Bearer ' . $jeton, 'Content-Type' => 'application/json', 'User-Agent' => 'piecemotooccasion-marketplace/1.0'],
        'body'    => $corps === null ? null : wp_json_encode($corps),
    ]);
    if (is_wp_error($reponse)) {
        return $reponse;
    }
    $code = wp_remote_retrieve_response_code($reponse);
    $json = json_decode(wp_remote_retrieve_body($reponse), true);
    if ($code >= 400) {
        return new WP_Error('piecemoto', isset($json['erreur']) ? $json['erreur'] : 'HTTP ' . $code);
    }
    return $json;
}

function piecemoto_categorie_produit($produit) {
    // La premiere categorie WooCommerce du produit, sinon la categorie par defaut du reglage.
    $termes = get_the_terms($produit->get_id(), 'product_cat');
    if (is_array($termes) && $termes) {
        return $termes[0]->name;
    }
    return get_option('piecemoto_categorie', 'Pièce moto') ?: 'Pièce moto';
}

function piecemoto_fiche($produit) {
    if (!$produit || $produit->get_status() !== 'publish') {
        return null;
    }
    $sku = $produit->get_sku();
    if (!$sku) {
        return null;
    }
    $images = [];
    foreach (array_merge([$produit->get_image_id()], $produit->get_gallery_image_ids()) as $id) {
        $url = $id ? wp_get_attachment_image_url($id, 'full') : '';
        if ($url) {
            $images[] = $url;
        }
    }
    return [
        'reference'    => $sku,
        'titre'        => $produit->get_name(),
        'description'  => wp_strip_all_tags($produit->get_short_description()),
        'information'  => wp_strip_all_tags($produit->get_description()),
        'prix_ttc'     => wc_get_price_including_tax($produit),
        'stock'        => $produit->managing_stock() ? (int) $produit->get_stock_quantity() : ($produit->is_in_stock() ? 1 : 0),
        'categorie'    => piecemoto_categorie_produit($produit),
        'images'       => array_slice($images, 0, 5),
        'url_boutique' => $produit->get_permalink(),
        'marque'       => (string) $produit->get_attribute('marque'),
        'modele'       => (string) $produit->get_attribute('modele'),
        'etat_achat'   => 'Occasion',
    ];
}

function piecemoto_synchroniser_tout() {
    $ids = wc_get_products(['status' => 'publish', 'limit' => -1, 'return' => 'ids']);
    $fiches = [];
    foreach ($ids as $id) {
        $fiche = piecemoto_fiche(wc_get_product($id));
        if ($fiche) {
            $fiches[] = $fiche;
        }
    }
    $envoyes = 0;
    $erreurs = [];
    foreach (array_chunk($fiches, PIECEMOTO_LOT) as $lot) {
        $r = piecemoto_appel('PUT', '/produits', ['produits' => $lot]);
        if (is_wp_error($r)) {
            $erreurs[] = $r->get_error_message();
            continue;
        }
        $envoyes += count($r['produits'] ?? []);
        foreach ($r['erreurs'] ?? [] as $e) {
            $erreurs[] = ($e['reference'] ?? '?') . ' : ' . ($e['erreur'] ?? '');
        }
    }
    update_option('piecemoto_derniere_sync', current_time('mysql'));
    return sprintf('%d produit(s) envoye(s) a PieceMotoOccasion, %d erreur(s). %s', $envoyes, count($erreurs), implode(' | ', array_slice($erreurs, 0, 5)));
}

// ---------------------------------------------------------------- synchronisation automatique

add_action('woocommerce_update_product', 'piecemoto_sync_produit', 20);
add_action('woocommerce_new_product', 'piecemoto_sync_produit', 20);
function piecemoto_sync_produit($id) {
    $fiche = piecemoto_fiche(wc_get_product($id));
    if ($fiche) {
        piecemoto_appel('PUT', '/produits', ['produits' => [$fiche]]);
    }
}

add_action('woocommerce_product_set_stock', function ($produit) {
    if ($produit && $produit->get_sku()) {
        piecemoto_appel('PATCH', '/produits/' . rawurlencode($produit->get_sku()) . '/stock', ['stock' => (int) $produit->get_stock_quantity()]);
    }
});

add_action('piecemoto_sync_quotidienne', 'piecemoto_synchroniser_tout');
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('piecemoto_sync_quotidienne')) {
        wp_schedule_event(time() + 3600, 'daily', 'piecemoto_sync_quotidienne');
    }
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('piecemoto_sync_quotidienne');
});

// ---------------------------------------------------------------- commandes PieceMotoOccasion -> WooCommerce

add_action('rest_api_init', function () {
    // Webhook serveur a serveur : pas d'utilisateur WordPress. L'acces est controle par la
    // signature HMAC-SHA256 du corps (jeton API), verifiee dans piecemoto_recevoir_commande().
    register_rest_route('piecemoto/v1', '/commande', [
        'methods'             => 'POST',
        'callback'            => 'piecemoto_recevoir_commande',
        'permission_callback' => 'piecemoto_signature_valide',
    ]);
});

function piecemoto_signature_valide(WP_REST_Request $requete) {
    $jeton = (string) get_option('piecemoto_jeton', '');
    $signature = (string) $requete->get_header('x-pmo-signature');
    if ($jeton === '' || $signature === '') {
        return false;
    }
    $attendue = 'sha256=' . hash_hmac('sha256', $requete->get_body(), $jeton);
    return hash_equals($attendue, $signature);
}

function piecemoto_texte($valeur, $longueur = 200) {
    return mb_substr(sanitize_text_field((string) $valeur), 0, $longueur);
}

function piecemoto_recevoir_commande(WP_REST_Request $requete) {
    $donnees = json_decode($requete->get_body(), true);
    if (!is_array($donnees) || ($donnees['evenement'] ?? '') !== 'commande.payee' || empty($donnees['commande']) || !is_array($donnees['commande'])) {
        return new WP_REST_Response(['ok' => true, 'ignore' => true], 200);
    }
    $c = $donnees['commande'];
    $c['id'] = absint($c['id'] ?? 0);
    if (!$c['id']) {
        return new WP_REST_Response(['erreur' => 'commande sans identifiant'], 400);
    }
    // Anti-doublon sans requete sur les metas : correspondance commande PieceMotoOccasion -> commande WooCommerce en option.
    $index = (array) get_option('piecemoto_commandes', []);
    if (!empty($index[$c['id']])) {
        return new WP_REST_Response(['ok' => true, 'deja' => true, 'commande_woocommerce' => (int) $index[$c['id']]], 200);
    }
    if (!get_option('piecemoto_creer_commandes', 1)) {
        wp_mail(sanitize_email(get_option('admin_email')), 'Commande PieceMotoOccasion #' . $c['id'], "Nouvelle commande payee sur PieceMotoOccasion, details dans votre espace vendeur : https://piecemotooccasion.eu/a2/vendeurs/boutique");
        return new WP_REST_Response(['ok' => true], 200);
    }
    $commande = wc_create_order();
    foreach ((array) ($c['lignes'] ?? []) as $ligne) {
        if (!is_array($ligne)) {
            continue;
        }
        $reference = piecemoto_texte($ligne['reference'] ?? '', 100);
        $id = $reference ? wc_get_product_id_by_sku($reference) : 0;
        $produit = $id ? wc_get_product($id) : null;
        $qte = max(1, absint($ligne['quantite'] ?? 1));
        $total = max(0, (float) ($ligne['total_ttc'] ?? 0));
        if ($produit) {
            $item_id = $commande->add_product($produit, $qte, ['subtotal' => $total, 'total' => $total]);
        } else {
            $item = new WC_Order_Item_Product();
            $item->set_name(piecemoto_texte($ligne['titre'] ?? 'Produit PieceMotoOccasion'));
            $item->set_quantity($qte);
            $item->set_subtotal($total);
            $item->set_total($total);
            $commande->add_item($item);
            $item_id = $item->get_id();
        }
        $details = array_filter(array_map('piecemoto_texte', [$ligne['couleur'] ?? '', $ligne['taille'] ?? '', $ligne['position'] ?? '', $ligne['texte_3d'] ?? '', $ligne['renseignement'] ?? '']));
        if ($details && $item_id) {
            wc_add_order_item_meta($item_id, 'Personnalisation', implode(' / ', $details));
        }
    }
    $liv = is_array($c['livraison'] ?? null) ? $c['livraison'] : [];
    $adresse = [
        'first_name' => piecemoto_texte($liv['prenom'] ?? ''), 'last_name' => piecemoto_texte($liv['nom'] ?? ''),
        'address_1'  => piecemoto_texte($liv['adresse'] ?? ''), 'postcode' => piecemoto_texte($liv['code_postal'] ?? '', 20),
        'city'       => piecemoto_texte($liv['ville'] ?? ''), 'country' => 'FR',
        'phone'      => piecemoto_texte($liv['telephone'] ?? '', 30), 'email' => sanitize_email($liv['email'] ?? ''),
    ];
    $commande->set_address($adresse, 'shipping');
    $commande->set_address($adresse, 'billing');
    $commande->set_created_via('piecemotooccasion');
    $commande->set_customer_note('Commande PieceMotoOccasion #' . $c['id'] . ' - livraison : ' . piecemoto_texte($liv['mode'] ?? ''));
    $commande->update_meta_data('_piecemoto_commande_id', (string) $c['id']);
    $commande->calculate_totals();
    $commande->set_status('processing', 'Commande payee sur PieceMotoOccasion (marketplace).');
    $commande->save();
    $index[$c['id']] = $commande->get_id();
    update_option('piecemoto_commandes', array_slice($index, -500, null, true), false);
    return new WP_REST_Response(['ok' => true, 'commande_woocommerce' => $commande->get_id()], 200);
}

// ---------------------------------------------------------------- expedition WooCommerce -> PieceMotoOccasion

add_action('woocommerce_order_status_completed', function ($order_id) {
    $commande = wc_get_order($order_id);
    $piecemoto_id = $commande ? $commande->get_meta('_piecemoto_commande_id') : '';
    if (!$piecemoto_id) {
        return;
    }
    $suivi = $commande->get_meta('_tracking_number') ?: $commande->get_meta('_wc_shipment_tracking_items');
    if (is_array($suivi)) {
        $suivi = $suivi[0]['tracking_number'] ?? '';
    }
    piecemoto_appel('POST', '/commandes/' . (int) $piecemoto_id . '/expedier', [
        'transporteur' => (string) ($commande->get_meta('_tracking_provider') ?: 'Transporteur'),
        'suivi'        => (string) ($suivi ?: 'expedie'),
    ]);
});
