<?php echo $messages; ?>
<form class="form-kill form-shield" action="<?php echo $config->url_current; ?>" method="post">
  <?php echo Form::hidden('token', $token); ?>
  <h3><?php echo $speak->shield . ': ' . $info->title; ?></h3>
  <?php if(strpos($config->url_current, 'file:') !== false): ?>
  <p><strong><?php echo $the_shield; ?></strong> <i class="fa fa-arrow-right"></i> <?php echo str_replace(DS, ' <i class="fa fa-arrow-right"></i> ', $the_path); ?></p>
  <pre><code><?php echo Text::parse(File::open(SHIELD . DS . $the_shield . DS . $the_path)->read(), '->encoded_html'); ?></code></pre>
  <?php else: ?>
  <?php if($files): ?>
  <ul>
    <?php foreach($files as $file): ?>
    <li><?php echo $file->path; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php endif; ?>
  <p>
  <?php echo UI::button('action', $speak->yes); ?>
  <?php echo UI::btn('reject', $speak->no, $config->url . '/' . $config->manager->slug . '/shield/' . $the_shield); ?>
  </p>
</form>