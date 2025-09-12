<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'softwareformlmprueba' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Yt/P0% )6-;,@3g(>%y9/aB[Utx{V?yWq`vDDRDqy|9,*3){@)[0,M B,,k$8I`&' );
define( 'SECURE_AUTH_KEY',  'N5%K4K5ee1tQ.>0x0s3RN!ht!8am?}7ZN^87`lgLcr~#a06i;^|r,f?+m/u.Aw8k' );
define( 'LOGGED_IN_KEY',    'nI1{`d}NmGw %C7)M)3m#HW`vxUn.~UnMdyzY[1gukI)|[/^;eX+-V| sW-20 &&' );
define( 'NONCE_KEY',        'D!29KM 9f_xTK<=~1l/PcIJC)oT?u9cD.,+OZ j)rN.V0y:B[hU.@5Kb~+8`JLRb' );
define( 'AUTH_SALT',        'q:w[cxyYkm@-{ [!5GX!AB6i*e90YPBXzm`3IV1Kz)}Uv/b+D&T%r7g1]-^@+A/k' );
define( 'SECURE_AUTH_SALT', 'wa7*Z*2FK<H!%@a,pq2KAc6tlw$-Mi=S&&31#~9Ek(~F[SqrDSSQe#7Q5VM>H<!U' );
define( 'LOGGED_IN_SALT',   '<}},-)n^r&_Q[ []N8WtI6~+5vb+N/@Y0Nv+$K(^?}&habLUH|pU@#B.Ea&> !,Z' );
define( 'NONCE_SALT',       '}}n>gui!p[(qM`x^fZHyJY8dyea-MPO7.)R]/}}5%^-QJx5bg_I_{CAC#aaP=[I-' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

define('FS_METHOD', 'direct');

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
