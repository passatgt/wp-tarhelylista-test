<?php
/*
Plugin Name: WP Tárhelylista Teszt
Plugin URI: https://tarhelylista.hu
Description: Szerver és WordPress sebesség teszt
Author: Viszt Péter
Version: 1.0
WC requires at least: 3.0.0
WC tested up to: 3.7.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WP_Tarhelylista_Teszt {
  public $benchmark_tool = null;
  private $_server_benchmark_results;
  private $_wp_benchmark_results;
  protected static $_instance = null;

	//Get main instance
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

  //Construct
	public function __construct() {

    //Banchmark helper
    require_once( plugin_dir_path( __FILE__ ) . 'class-benchmark.php' );
    $this->benchmark_tool = new WP_Tarhelylista_Teszt_Benchmark();

    //Plugin loaded
		add_action( 'plugins_loaded', array( $this, 'init' ) );
  }

  public function init() {
    add_action('admin_menu', array( $this, 'create_menu' ));
  }

	//Create submenu in Tools
	public function create_menu() {
    $hook = add_management_page( 'Tárhelylista Teszt', 'Tárhelylista Teszt', 'install_plugins', 'wp_tarhelylista_teszt', array( $this, 'generate_page_content' ) );
    add_action( "load-$hook", array( $this, 'process_page_submit' ) );
	}

  function generate_page_content() {
    ?>
    <div class="wrap">
      <h1>WP Tárhelylista Teszt</h1>
      <p style="max-width:750px;">Ez a bővítmény ellenőrizni fogja különböző műveletek futtatását a szervereden és meghatároz egy értéket, amit a tárhelylista.hu oldalon használunk fel a sebesség pontszám meghatározásánál. Kattints a gombra a teszt futtatásához. A pontos eredményhez próbáld meg egymás után többször is lefuttatni és átlagold az eredményeket. A teszt akár ety percig is eltarhat, légy türelemmel.</p>
      <form method="post">
  			<?php wp_nonce_field( 'run_test', 'wp_tarhelylista_teszt_nonce' ); ?>
        <div style="display:flex">
    			<?php submit_button( 'Szerver teszt futtatása', 'primary', 'wp_tarhelylista_teszt_server_run' ); ?>
          <div style="width:20px"></div>
          <?php submit_button( 'Wordpress teszt futtatása', 'primary', 'wp_tarhelylista_teszt_wp_run' ); ?>
        </div>
  		</form>

      <?php if($this->_server_benchmark_results): ?>
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th>Teszt</th>
            <th>Futási idő (másodperc)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Math</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['math']; ?></td>
          </tr>
          <tr>
            <td>String Manipulation</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['string']; ?></td>
          </tr>
          <tr>
            <td>Loops</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['loops']; ?></td>
          </tr>
          <tr>
            <td>Conditionals</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['ifelse']; ?></td>
          </tr>
          <tr>
            <td>Mysql Connect</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['mysql_connect']; ?></td>
          </tr>
          <tr>
            <td>Mysql Select Database</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['mysql_select_db']; ?></td>
          </tr>
          <tr>
            <td>Mysql Query Version</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['mysql_query_version']; ?></td>
          </tr>
          <tr>
            <td>Mysql Query Benchmark</td>
            <td><?php echo $this->_server_benchmark_results['benchmark']['mysql_query_benchmark']; ?></td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <th>Futási idő összesen (másodperc)</th>
            <th><?php echo $this->_server_benchmark_results['benchmark']['total']; ?></th>
          </tr>
        </tfoot>
      </table>
      <?php endif; ?>

      <?php if($this->_wp_benchmark_results): ?>
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th>Futási idő (másodperc)</th>
            <th>WordPress műveletek másodpercenként</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <td><?php echo $this->_wp_benchmark_results['time']; ?></td>
            <td><?php echo $this->_wp_benchmark_results['operations']; ?></td>
          </tr>
        </tfoot>
      </table>
      <?php endif; ?>

    </div>
    <?php
  }

  public function process_page_submit() {
    if ( ! empty( $_POST['wp_tarhelylista_teszt_server_run'] ) ) {
      check_admin_referer( 'run_test', 'wp_tarhelylista_teszt_nonce' );
      $this->_server_benchmark_results = $this->benchmark_tool->server_benchmark();
      add_action( 'admin_notices', array( $this, 'display_notice' ) );
    } else if ( ! empty( $_POST['wp_tarhelylista_teszt_wp_run'] ) ) {
      check_admin_referer( 'run_test', 'wp_tarhelylista_teszt_nonce' );
      $this->_wp_benchmark_results = $this->benchmark_tool->wordpress_benchmark();
      add_action( 'admin_notices', array( $this, 'display_notice' ) );
    }
  }

  public function display_notice() {
    ?>
		<div class="notice notice-success is-dismissible">
			<p>A teszt sikeresen lefutott, a lenti táblázatban látod az eredményeket.</p>
		</div>
		<?php
  }

}

function WP_Tarhelylista_Teszt() {
  return WP_Tarhelylista_Teszt::instance();
}
$GLOBALS['wp_tarhelylista_teszt'] = WP_Tarhelylista_Teszt();
