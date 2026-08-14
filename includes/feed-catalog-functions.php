<?php
/**
 * Point d'entrée générique du catalogue indexé de flux/API providers, exposé
 * aux autres composants du core (crons, `SearchApi`) et aux add-ons providers.
 *
 * @package AllI1D
 */

// Durée sans être revu en rafraîchissement au-delà de laquelle une entrée du
// catalogue est considérée disparue du tracker et purgée.
if ( ! defined( 'ALLI1D_FEED_CATALOG_STALE_TTL' ) ) {
	define( 'ALLI1D_FEED_CATALOG_STALE_TTL', 7 * DAY_IN_SECONDS );
}

if ( ! function_exists( 'alli1d_index_feed_catalog' ) ) {
	/**
	 * Indexer (insérer ou mettre à jour) une liste d'items d'un provider dans
	 * le catalogue local. Appelée par chaque add-on provider, en réponse à
	 * l'action broadcast `alli1d_refresh_feed_catalog`, ou immédiatement après
	 * un appel API de secours (cf. `alli1d_find_cached_catalog_items()`).
	 *
	 * @param string                           $provider Le slug du provider (ex. 'tr4ker', 'c411').
	 * @param string                           $type     'movie' | 'tvshow'.
	 * @param array<int, array<string, mixed>> $items Items au format contrat commun (id, title, quality, language, score, extra).
	 * @return int Le nombre d'items traités.
	 */
	function alli1d_index_feed_catalog( string $provider, string $type, array $items ): int {
		return \AllI1D\Models\Repositories\FeedCatalogRepository::get_instance()->upsert_items( $provider, $type, $items );
	}
}

if ( ! function_exists( 'alli1d_find_cached_catalog_items' ) ) {
	/**
	 * Rechercher des candidats déjà indexés dans le catalogue local, par titre.
	 *
	 * Rétrocompatibilité : si aucun add-on provider n'a jamais alimenté le
	 * catalogue (désactivé, tout juste installé), cette fonction renvoie
	 * simplement un tableau vide — aucune erreur, aucun appel réseau implicite.
	 * C'est à l'appelant de décider s'il tente un appel API de secours sur un
	 * résultat vide.
	 *
	 * @param string      $title    Le titre (ou fragment) recherché.
	 * @param string|null $type     Filtrer par type ('movie'|'tvshow'), ou null pour tous.
	 * @param string|null $provider Filtrer par provider, ou null pour tous.
	 * @return array<int, array<string, mixed>> Les items correspondants, au format contrat commun.
	 */
	function alli1d_find_cached_catalog_items( string $title, ?string $type = null, ?string $provider = null ): array {
		return \AllI1D\Models\Repositories\FeedCatalogRepository::get_instance()->search( $title, $type, $provider );
	}
}
