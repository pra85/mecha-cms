<?php Shield::chunk('article.time'); ?>
<h4 class="post-title">
  <?php if($article->link): ?>
  <a href="<?php echo $article->link; ?>"><?php echo $article->title; ?></a>
  <?php elseif($article->url): ?>
  <a href="<?php echo $article->url; ?>"><?php echo $article->title; ?></a>
  <?php else: ?>
  <?php echo $article->title; ?>
  <?php endif; ?>
</h4>
<?php if(Weapon::exist('article_header')): ?>
<div><?php Weapon::fire('article_header', array($article)); ?></div>
<?php endif; ?>