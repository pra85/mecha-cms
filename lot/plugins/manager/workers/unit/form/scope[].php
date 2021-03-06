<div class="grid-group">
  <span class="grid span-1 form-label"><?php echo $speak->scope; ?></span>
  <span class="grid span-5">
  <?php

  $cache = Guardian::wayback('scope', isset($page->scope_raw) ? $page->scope_raw : $segment);
  $cache = ',' . Request::get('scope', is_array($cache) ? implode(',', $cache) : $cache) . ',';
  sort($scopes);
  foreach($scopes as $scope) {
      $scope_text = isset($speak->{$scope}) ? $speak->{$scope} : Text::parse($scope, '->title');
      echo '<div>' . Form::checkbox('scope[]', $scope, strpos($cache, ',' . $scope . ',') !== false, $scope_text) . '</div>';
  }

  ?>
  </span>
</div>