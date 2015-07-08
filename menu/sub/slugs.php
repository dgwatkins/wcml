<table class="widefat wpml-list-table wp-list-table striped" cellspacing="0">
	<thead>
	<tr>
		<?php //TODO Sergey: make Title and Date columns sortable ?>

		<th scope="col"><?php _e( 'Slug type', 'wpml-wcml' ) ?></th>
		<th scope="col" id="date" class="wpml-col-url">
			<span class="icl-title-flag">
				<img src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"/>
			</span>
			<?php _e( 'Original Slug', 'wpml-wcml' ) ?>
		</th>
		<th scope="col" class="wpml-col-languages">
			<span title="Spanish"><img
					src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/es.png" alt=""></span>
			<span title="Polish"><img
					src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/pl.png" alt=""></span>
			<span title="Ukrainian"><img
					src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/uk.png" alt=""></span>
		</th>


	</tr>
	</thead>
	<tbody>

	<tr>
		<td>
			<strong>
				<?php _e( 'Shop page', 'wpml-wcml' ); ?>
			</strong>
		</td>

		<td class="wpml-col-url">
			<?php bloginfo( 'url' ); ?>/<strong>shop</strong>
		</td>


		<td class="wpml-col-languages">
			<a id="es" title="Spanish: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
			<a id="pl" title="Polish: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
			<a id="uk" title="Ukrainian: Add translation">
				<i class="otgs-ico-refresh"></i>
			</a>
		</td>

	</tr>
	<tr>
		<td>
			<strong>
				<?php _e( 'Product(s) page(s) base', 'wpml-wcml' ); ?>
			</strong>
		</td>

		<td class="wpml-col-url">
			<?php bloginfo( 'url' ); ?>/<strong>product</strong>/blue-box
		</td>


		<td class="wpml-col-languages">
			<a id="es" title="Spanish: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
			<a id="pl" title="Polish: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
			<a id="uk" title="Ukrainian: Add translation">
				<i class="otgs-ico-refresh"></i>
			</a>
		</td>


	</tr>
	<tr>
		<td>
			<strong>
				<?php _e( 'Product category base', 'wpml-wcml' ); ?>
			</strong>
		</td>

		<td class="wpml-col-url">
			<?php bloginfo( 'url' ); ?>/<strong>product-category</strong>/time-lords
		</td>


		<td class="wpml-col-languages">
			<a id="es" title="Spanish: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
			<a id="pl" title="Polish: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
			<a id="uk" title="Ukrainian: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
		</td>


	</tr>
	<tr>
		<td>
			<strong>
				<?php _e( 'Product tag base', 'wpml-wcml' ); ?>
			</strong>
		</td>

		<td class="wpml-col-url">
			<?php bloginfo( 'url' ); ?>/<strong>product-tag</strong>/doctor
		</td>


		<td class="wpml-col-languages">
			<a id="es" title="Spanish: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
			<a id="pl" title="Polish: Add translation">
				<i class="otgs-ico-add"></i>
			</a>
			<a id="uk" title="Ukrainian: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
		</td>

	</tr>
	<tr>
		<td>
			<strong>
				<?php _e( 'Product attribute base', 'wpml-wcml' ); ?>
			</strong>
		</td>

		<td class="wpml-col-url">
			<?php bloginfo( 'url' ); ?>/<strong>product-attribute</strong>/atrribute-name/timeless
		</td>


		<td class="wpml-col-languages">
			<a id="es" title="Spanish: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
			<a id="pl" title="Polish: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
			<a id="uk" title="Ukrainian: Add translation">
				<i class="otgs-ico-edit"></i>
			</a>
		</td>


	</tr>


	</tbody>
</table>
