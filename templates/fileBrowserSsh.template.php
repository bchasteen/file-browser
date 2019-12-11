<div style="margin:0 1rem">
	<div>{BackLink}</div>
	<div>{Message}</div>
	<div>
		<form name="frmSsh" method="post">
			Command: <input type="text" value="{Ssh}" id="ssh_command" name="ssh_command"  size="70">
			<input type="submit" value="GO"/>
		</form>
	</div>
	<div style="margin:1rem">{AResult}</div>
</div>
<script>
	var input = document.getElementById("ssh_command")
	input.focus();
	input.selectionStart = input.selectionEnd = input.value.length;
	input.select();
</script>
