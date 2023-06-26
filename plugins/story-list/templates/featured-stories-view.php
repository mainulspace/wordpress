<div class="wrap top_stories_container">
	<div class="story_list_cont">
		<?php
		// Retrieve top stories posts
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 20,
			'meta_key'       => 'fetured_stories_sort_order',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => 'hrgmu_m_fetured_stories',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);
		$posts_list = new WP_Query( $args );
		?>

		<div class="featured-stories-title">
			<h2>Featured Stories</h2>
			<input type="submit" name="Submit" class="button-primary sortable_submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</div>

		<ul id="sorted_list" class="ct_sort_list">
			<?php if ( $posts_list->have_posts() ) :
				$i = 1;
				while ( $posts_list->have_posts() ) :
					$posts_list->the_post();
					?>
					<li id="<?php the_ID(); ?>">
						<?php echo $i . '. ' . get_the_title() . ' <span class="postedDate">' . get_the_time( 'g:i a j F Y' ) . ' </span> '; ?>
						<button class="st_delete">X</button>
					</li>
					<?php
					$i++;
				endwhile;
				wp_reset_postdata();
			endif; ?>
		</ul>
	</div>

	<div class="story_list_cont" id="recentstories">
		<input class="search" placeholder="Search" />
		<?php
		// Retrieve recent stories posts
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 300,
			'meta_query'     => array(
				array(
					'key'     => 'hrgmu_m_fetured_stories',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'hrgmu_m_top_stories',
					'compare' => 'NOT EXISTS',
				),
			),
		);
		$posts_list = new WP_Query( $args );
		?>

		<h2>Recent Stories</h2>

		<ul id="un_sorted_list" class="ct_sort_list list">
			<?php if ( $posts_list->have_posts() ) :
				while ( $posts_list->have_posts() ) :
					$posts_list->the_post();
					?>
					<li id="<?php the_ID(); ?>">
						<?php echo '<span class="story-title">' . get_the_title() . '</span> ' . ' <span class="postedDate">' . get_the_time( 'g:i a j F Y' ) . ' </span>'; ?>
						<button class="st_delete">X</button>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
			endif; ?>
		</ul>
	</div>

	<div class="clear"></div>

	<?php include 'top-stories-view.php'; ?>

	<p class="submit">
			<input type="submit" name="Submit" class="button-primary sortable_submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	</p>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".ct_sort_list").sortable({
				cursor: "move",
				connectWith: ".sort_list",
				update: function(event, ui) {

				}
			});

			$('.sortable_submit').on('click', function(e) {
				$('#sort_loading').css('display', 'block');
				$('.sort_updated').css('display', 'none');
				$('.sort_error').css('display', 'none');
				var data = {
					action: 'fetured-stories-sort',
					sort: $('#sorted_list').sortable('toArray'),
				};

				$.post(ajaxurl, data)
					.done(function(response) {
						$('#sort_loading').css('display', 'none');
						$('.sort_updated').css('display', 'block');
					}).fail(function() {
						$('#sort_loading').css('display', 'none');
						$('.sort_error').css('display', 'block');
					});
			});

			$('.st_delete').on('click', function() {
				$(this).parent().remove();
			});
		});
	</script>

</div>