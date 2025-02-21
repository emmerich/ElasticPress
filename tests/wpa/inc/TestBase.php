<?php
/**
 * Basic test class
 *
 * @package elasticpress
 */

use \WPAcceptance\PHPUnit\Database;

/**
 * PHPUnit test class
 */
class TestBase extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * ElasticPress indexes
	 *
	 * @var array
	 */
	protected $indexes = [];

	/**
	 * Setup functionality
	 */
	public function setUp() {
		static $initialized = false;

		parent::setUp();

		if ( ! $initialized ) {
			$initialized = true;

			/**
			 * Delete all current indexes before we start
			 */
			$cluster_indexes = json_decode( $this->runCommand( 'wp elasticpress get-cluster-indexes' )['stdout'], true );

			foreach ( $cluster_indexes as $index ) {
				$this->runCommand( 'wp elasticpress delete-index --index-name=' . $index['index'] );
			}

			$this->indexes = json_decode( $this->runCommand( 'wp elasticpress get-indexes' )['stdout'], true );

			/**
			 * Set default feature settings
			 */
			$this->updateFeatureSettings(
				[
					'search'            => [
						'active' => 1,
					],
					'related_posts'     => [
						'active' => 1,
					],
					'facets'            => [
						'active' => 1,
					],
					'searchordering'    => [
						'active' => 1,
					],
					'autosuggest'       => [
						'active' => 1,
					],
					'woocommerce'       => [
						'active' => 0,
					],
					'protected_content' => [
						'active'         => 0,
						'force_inactive' => 1,
					],
					'users'             => [
						'active' => 1,
					],
				]
			);
		}
	}

	/**
	 * Update feature settings
	 *
	 * @param  array $feature_settings Feature settings
	 */
	public function updateFeatureSettings( $feature_settings ) {
		$current_settings_row = $this->selectRowsWhere( [ 'option_name' => 'ep_feature_settings' ], 'options' );

		if ( empty( $current_settings_row ) ) {
			$current_settings = [];
		} else {
			$current_settings = unserialize( $current_settings_row['option_value'] );
		}

		foreach ( $feature_settings as $feature => $settings ) {
			if ( ! empty( $current_settings[ $feature ] ) ) {
				$feature_settings[ $feature ] = array_merge( $current_settings[ $feature ], $settings );
			}
		}

		$this->updateRowsWhere(
			[
				'option_value' => $feature_settings,
			],
			[
				'option_id' => $current_settings_row['option_id'],
			],
			'options'
		);
	}

	/**
	 * Publish a post in the admin
	 * @param  array                       $data  Post data
	 * @param  \WPAcceptance\PHPUnit\Actor $actor Current actor
	 */
	public function publishPost( array $data, \WPAcceptance\PHPUnit\Actor $actor ) {
		$defaults = [
			'title'   => 'Test Post',
			'content' => 'Test content.',
		];

		$data = array_merge( $defaults, $data );

		$actor->moveTo( 'wp-admin/post-new.php' );

		$actor->click( '.nux-dot-tip__disable' );

		$actor->typeInField( '#post-title-0', $data['title'] );

		usleep( 100 );

		$actor->waitUntilElementVisible( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-panel__toggle' );

		$actor->click( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementVisible( '.editor-post-publish-button' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-button' );

		$actor->click( '.editor-post-publish-button' );

		$actor->waitUntilElementVisible( '.components-notice' );
	}
}
