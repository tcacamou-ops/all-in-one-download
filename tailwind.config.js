/**
 * Tailwind config used only to compile a static, versioned CSS asset
 * (assets/css/tailwind-build.css) for the plugin's admin screens.
 *
 * This replaces the previous runtime dependency on the Tailwind Play CDN
 * (https://cdn.tailwindcss.com), which served a dynamically generated,
 * unversioned script with no reliable Subresource Integrity hash.
 *
 * Run `npm install && npm run build:css` after changing markup that uses
 * Tailwind utility classes in `includes/**\/*.php`.
 */
module.exports = {
	content: [ './includes/**/*.php' ],
	theme: {
		extend: {},
	},
	plugins: [
		require( '@tailwindcss/forms' ),
		require( '@tailwindcss/container-queries' ),
	],
};
