<div class="grid-group">
  <?php $_ = 'unit:' . time(); ?>
  <label class="grid span-1 form-label" for="<?php echo $_; ?>"><?php echo $speak->message; ?></label>
  <span class="grid span-5">
  <?php echo Form::textarea('message', Request::get('message', Guardian::wayback('message', $page->message_raw)), null, array(
      'class' => array(
          'textarea-block',
          'textarea-expand',
          'MTE',
          'code'
      ),
      'id' => $_
  )); ?>
  </span>
</div>