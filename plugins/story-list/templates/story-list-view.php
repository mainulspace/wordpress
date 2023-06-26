<?php
// Grab all the request parameters
$story_data = $_REQUEST;

// Check if any stories are selected (if not, default to Featured stories)
$storyType = isset($story_data['stories']) ? $story_data['stories'] : 1;
?>

<?php include('header.php'); ?>

<div class="wrap">
	<h1>Manage Contents</h1>
	<ul class="subsubsub">
		<li class="featured_stories">
			<a <?php echo ($storyType == 1) ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=1">Featured / Top Stories</a> |
		</li>
		<li class="worth_reading_stories">
			 <a <?php echo ($storyType == 3) ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=3">Worth Reading Stories</a> |
		</li>
		<li class="latest_stories">
			<a <?php echo ($storyType == 4) ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=4">Latest Stories</a>
		</li>
	</ul>
	<div class="clear"></div>
</div>

<?php
// Handle different story types
switch ($storyType) {
	case 1:
		include('featured-stories-view.php');
		break;
	case 3:
		include('worth-reading-stories-view.php');
		break;
	case 4:
		include('latest-stories-view.php');
		break;
	default:
		echo 'Invalid story type.';
		break;
}
?>

<?php include('footer.php'); ?>