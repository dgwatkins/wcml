<?php

/**
 * @author OnTheGo Systems
 *
 * @group  templates
 */
class Test_Plugins_Wrap extends OTGS_TestCase {
	public function setUp() {
		parent::setUp();
		WP_Mock::passthruFunction( 'wp_kses_post');
	}

	/**
	 * @test
	 */
	public function it_renders_the_title() {
		$model = [
			'strings' => [
				'title' => 'WooCommerce Multilingual',
			],
		];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$title_element = $document->first( 'h1' );

		$this->assertSame( $model['strings']['title'], $title_element->text() );
	}

	/**
	 * @test
	 */
	public function it_renders_the_tab() {
		$model = [
			'link_url' => 'An url',
			'strings'  => [
				'required' => 'There is something required',
			],
		];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$this->assertTrue( $document->has( 'nav' ) );

		$anchor = $document->first( 'nav' )->first( 'a' );

		$this->assertSame( $model['link_url'], $anchor->attr( 'href' ) );
		$this->assertSame( $model['strings']['required'], $anchor->text() );
	}

	/**
	 * @test
	 */
	public function it_renders_the_section_header() {
		$model = [
			'link_url' => 'An url',
			'strings'  => [
				'plugins' => 'Plugins Status',
				'depends' => 'A long sentence, saying that WCML depends on other plugins',
			],
		];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$this->assertTrue( $document->has( 'h3' ) );

		$section_header = $document->first( 'h3' );
		$this->assertTrue( $section_header->has( 'i' ) );

		$this->assertSame( $model['strings']['plugins'], trim( $section_header->text() ) );
		$this->assertSame( $model['strings']['depends'], trim( $section_header->first( 'i' )->attr( 'data-tip' ) ) );
	}

	/**
	 * @test
	 */
	public function it_renders_the_items() {
		$model = [];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$this->assertSame( 4, $document->count( 'li i' ) );

		$classes = [
			'wpml-multilingual-cms',
			'wpml-translation-management',
			'wpml-string-translation',
			'woocommerce'
		];

		foreach ( $classes as $index => $class ) {
			$this->assertSame( 1, $document->count( 'li i.otgs-ico-warning.' . $class ) );
		}
	}

	/**
	 * @test
	 */
	public function it_renders_the_old_wpml_item() {
		$model = [
			'old_wpml'      => true,
			'tracking_link' => 'A tracking link',
			'strings'       => [
				'old_wpml_link' => 'A link',
				'update_wpml'   => 'A message asking to update WPML',
			],
		];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$this->assertSame( 1, $document->count( 'li i.wpml-multilingual-cms' ) );
		$this->assertContains( $model['strings']['old_wpml_link'], $document->first( 'li' )->text() );
		$this->assertContains( $model['strings']['update_wpml'], $document->first( 'li' )->text() );
	}

	/**
	 * @test
	 */
	public function it_renders_the_icl_version_item() {
		$model = [
			'icl_version'   => true,
			'icl_setup'     => true,
			'tracking_link' => 'A tracking link',
			'strings'       => [
				'wpml'        => 'WPML',
				'inst_active' => 'A message asking to update %s',
				'is_setup'    => 'A message saying that %s is set up',
			],
		];

		$output = $this->get_rendered_template( $model, WCML_PLUGIN_PATH . '/templates/php/plugins-wrap.php' );

		$document = new \DiDom\Document( $output );

		$this->assertSame( 2, $document->count( 'li i.wpml-multilingual-cms' ) );

		$li_elements = $document->find( 'li' );
		$this->assertContains( sprintf( $model['strings']['inst_active'], $model['strings']['wpml'] ), $li_elements[0]->text() );
		$this->assertContains( sprintf( $model['strings']['is_setup'], $model['strings']['wpml'] ), $li_elements[1]->text() );
	}

	/**
	 * @param array|object $model
	 *
	 * @return false|string
	 */
	protected function get_rendered_template( $model, $template_path ) {
		$model = new \WPML\Templates\PHP\Model( $model );
		ob_start();
		include $template_path;

		return ob_get_clean();

	}

}
