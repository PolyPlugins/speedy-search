<?php

namespace PolyPlugins\Speedy_Search;

use TeamTNT\TNTSearch\TNTSearch as TNTSearchEngine;

if (!defined('ABSPATH')) exit;

class TNTSearch {
  
  private $index_path;
  private $tnt;
  private static $instance = null;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct(){
    $this->index_path = WP_CONTENT_DIR . '/indexes/';

    $this->init();
  }
    
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $is_missing_extensions = Utils::is_missing_extensions();
    $database_type         = Utils::get_option('database_type') ?: 'mysql';

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    if ($database_type === 'sqlite') {
      if (!$this->index_path_exists()) {
        $this->create_index_path();
        $this->secure_index_path();
      }
    }

    $this->init_tnt();
  }
  
  /**
   * Init TNT
   *
   * @return void
   */
  public function init_tnt() {
		$database_type = Utils::get_option('database_type') ?: 'mysql';
    $mysql_host    = '';

    if ($database_type === 'mysql') {
      if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASSWORD')) {
        return;
      }
      
      $mysql_host = $this->get_mysql_pdo_host(DB_HOST);
      
      if (!$mysql_host) {
        return;
      }
    }

    $this->tnt = new TNTSearchEngine;
    
    if ($database_type === 'mysql') {
      $this->tnt->loadConfig(array(
        'driver'    => 'mysql',
        'engine'    => \TeamTNT\TNTSearch\Engines\MysqlEngine::class,
        'host'      => $mysql_host,
        'database'  => DB_NAME,
        'username'  => DB_USER,
        'password'  => DB_PASSWORD,
        'storage'   => $this->index_path,
        'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class,
        // 'tokenizer' => \PolyPlugins\Speedy_Search\WordPress_Tokenizer::class
      ));
    } else {
      $this->tnt->loadConfig(array(
        'driver'    => 'filesystem',
        'storage'   => $this->index_path,
        'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class
      ));
    }
  }
  
  /**
   * Get TNTSearch
   *
   * @return object
   */
  public function tnt() {
    return $this->tnt;
  }
  
  /**
   * Get instance
   *
   * @return object
   */
  public static function get_instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }
  
  /**
   * Check if the index path exists
   *
   * @return void
   */
  private function index_path_exists() {
    $index_path_exists = is_dir($this->index_path) ? true : false;
    
    return $index_path_exists;
  }

  /**
   * Get index path
   *
   * @return string $index_path the index path
   */
  public function get_index_path() {
    return $this->index_path;
  }
  
  /**
   * Create index path
   *
   * @return void
   */
  private function create_index_path() {
    $wp_filesystem = $this->get_wp_filesystem();

    if ($wp_filesystem) {
      $wp_filesystem->mkdir($this->index_path, 0755, true);
    }
  }
  
  /**
   * Secure the index path
   *
   * @return void
   */
  private function secure_index_path() {
    $wp_filesystem = $this->get_wp_filesystem();

    if ($wp_filesystem) {
      $wp_filesystem->put_contents($this->index_path . '.htaccess', "Deny from all\n", FS_CHMOD_FILE);
    }
  }

  /**
   * Get initialized WordPress filesystem object
   *
   * @return WP_Filesystem_Base|false
   */
  private function get_wp_filesystem() {
    global $wp_filesystem;

    if (!function_exists('WP_Filesystem')) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if (!$wp_filesystem) {
      WP_Filesystem();
    }

    return $wp_filesystem ? $wp_filesystem : false;
  }

  /**
   * Convert WordPress DB_HOST into a PDO-friendly host DSN segment.
   *
   * Handles:
   * - host
   * - host:port
   * - host:/path/to/mysql.sock
   *
   * @param  string $db_host Raw DB_HOST
   * @return string
   */
  private function get_mysql_pdo_host($db_host) {
    $db_host = trim((string) $db_host);
    
    if (!$db_host) {
      return '';
    }

    $socket_pos = strpos($db_host, ':/');
    if ($socket_pos !== false) {
      $host   = substr($db_host, 0, $socket_pos);
      $socket = substr($db_host, $socket_pos + 1);
      
      if (!$host || !$socket) {
        return '';
      }
      
      return $host . ';unix_socket=' . $socket;
    }

    if (substr_count($db_host, ':') === 1) {
      $parts = explode(':', $db_host, 2);
      $host  = isset($parts[0]) ? trim($parts[0]) : '';
      $port  = isset($parts[1]) ? trim($parts[1]) : '';
      
      if ($host && ctype_digit($port)) {
        return $host . ';port=' . $port;
      }
    }

    return $db_host;
  }

}
