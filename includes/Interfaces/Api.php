<?php
namespace AllI1D\Interfaces;

interface Api {
	/**
	 * Constructeur de l'API.
	 *
	 * @param string $route_namespace Le namespace de la route.
	 */
	public function __construct( string $route_namespace );
	/**
	 * Retourne le namespace complet de l'API.
	 *
	 * @return string
	 */
	public function get_namespace(): string;

	/**
	 * Vérifie les permissions pour accéder aux routes de l'API.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool;

	/**
	 * Retourne les routes disponibles pour cette API.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array;

	/**
	 * Enregistre les routes REST pour cette API.
	 *
	 * @return void
	 */
	public function register_routes(): void;
}
