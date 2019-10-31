<div>
	<div>{BackLink}</div>
	<div>{Message}</div>
	<div class="border">
	<form name="frmedit" method="post">
	<p><strong>File Name: {File2Edit}</strong></p>
	<textarea id="editfile" name="editfile" style="height:400px; width:100%;white-space:pre;" wrap="off">{Contents}</textarea>
	<script type="text/javascript">// generate_wysiwyg('editfile');</script>
	<p><input type="text" name="button" value="SAVEFILE" style="display:none"/>
	<input type="checkbox" name="Write_backup" value="1" id="Write_backup" title="Write backup"/>
	<label for="Write_backup">
	<strong>Write backup</strong>
	</label><br/></p>
	<p><input type="submit" value="SAVE"/></p>
	</form>
	</div>
</div>