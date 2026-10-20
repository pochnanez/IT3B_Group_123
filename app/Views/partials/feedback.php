<?php if ($message = session()->getFlashdata('success')): ?>
<div class="feedback" role="status"><?= esc($message) ?></div>
<?php endif ?>
<?php if ($message = session()->getFlashdata('error')): ?>
<div class="feedback" role="alert"><?= esc($message) ?></div>
<?php endif ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
<div class="feedback" role="alert"><p>Please check the following:</p><ul>
<?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?>
</ul></div>
<?php endif ?>
