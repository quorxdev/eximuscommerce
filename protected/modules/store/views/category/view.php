<?php
/**
 * Category view
 */

// Set meta tags
$this->pageTitle = ($model->meta_title) ? $model->meta_title : $model->name;
$this->pageKeywords = $model->meta_keywords;
$this->pageDescription = $model->meta_description;

$this->breadcrumbs = array(
	$model->name
);
?>

<h3><?php echo CHtml::encode($model->name); ?></h3>

<div class="row show-grid">
	<?php
	$this->widget('zii.widgets.CListView', array(
		'dataProvider'=>$provider,
		'ajaxUpdate'=>false,
		'template'=>'{sorter} {items} {pager} {summary}',
		'itemView'=>'_product',
		'sortableAttributes'=>array(
			'name', 'price'
		),
	));
	?>
</div>