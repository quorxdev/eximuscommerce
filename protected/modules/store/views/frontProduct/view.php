<?php
/**
 * Product view
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
    <!-- Left column  -->
    <div class="span-one-third">
        <a href="#">
            <img class="thumbnail" src="http://placehold.it/300x230" alt="">
        </a>
    </div>
    <!-- Right column -->
    <div class="span-two-thirds">
        <p><?php echo $model->short_description; ?></p>
        <p><?php echo $model->full_description; ?></p>

        <h4>Цена: <?php echo $model->price ?></h4>
        <input type="submit" class="btn success" value="Купить">

    </div>
</div>