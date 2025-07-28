<?php

namespace PolyPlugins\Speedy_Search;

use TeamTNT\TNTSearch\TNTSearch as TNTSearchEngine;

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

    if ($database_type === 'mysql') {
      if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASSWORD')) {
        return;
      }
    }

    $this->tnt = new TNTSearchEngine;
    
    if ($database_type === 'mysql') {
      $this->tnt->loadConfig(array(
        'driver'    => 'mysql',
        'engine'    => \TeamTNT\TNTSearch\Engines\MysqlEngine::class,
        'host'      => DB_HOST,
        'database'  => DB_NAME,
        'username'  => DB_USER,
        'password'  => DB_PASSWORD,
        'storage'   => $this->index_path,
        'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class,
        'tokenizer' => \TeamTNT\TNTSearch\Support\Tokenizer::class
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
    mkdir($this->index_path, 0755, true);
  }
  
  /**
   * Secure the index path
   *
   * @return void
   */
  private function secure_index_path() {
    file_put_contents($this->index_path . '.htaccess', "Deny from all\n");
  }

}
