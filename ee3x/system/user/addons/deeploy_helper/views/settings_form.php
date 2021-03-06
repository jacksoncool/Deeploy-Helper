<div class="box">

<h1><?=lang('quick_replace')?></h1>
<div id="quick-replace-form" class="settings">
	<fieldset class="col-group ">
		<div class="setting-txt col  w-8">
			<h3><?=lang('current_document_root')?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=$_SERVER["DOCUMENT_ROOT"]?>
		</div>
	</fieldset>
	<fieldset class="col-group ">
		<div class="setting-txt col  w-8">
			<h3><?=lang('find_text')?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<input name="find" value="" id="find" maxlength="100" size="75" class="fullfield" onchange="this.style.color='#ff1212';" type="text">
		</div>
	</fieldset>
	<fieldset class="col-group ">
		<div class="setting-txt col  w-8">
			<h3><?=lang('replace_text')?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<input name="replace" value="" id="replace" maxlength="100" size="75" class="fullfield" onchange="this.style.color='#ff1212';" type="text">
		</div>
	</fieldset>
	<fieldset class="form-ctrls ">
		<input name="quick_replace" value="Quick Replace" id="quick_replace" class="btn submit" type="submit">
	</fieldset>
</div>

<?php $this->embed('ee:_shared/form')?>


</div>