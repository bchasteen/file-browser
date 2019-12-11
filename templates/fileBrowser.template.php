	<div class="container">
		<div>{Message}</div>
		<div class="border padding margin-top margin-bottom">
			<form action="" method="POST" enctype="multipart/form-data">
				<p><input type="text" name="dir" value="{Dir}" style="display:none"/>
				<input type="file" onKeypress="event.cancelBubble=true;" name="myfile" class="">
				<input title="Upload selected file to the current working directory" type="Submit" class="button khaki" name="Submit" value="&#8679; Upload"/></p>
				<p>
				<input class="button blue" type="button" name="button" value="Launch Shell Program"  onclick="window.location = '?cmd=ssh'">
				</p>
			</form>
		</div>
		
		<form action="" method="Post" name="filelist">
			<div style="overflow:auto;padding-bottom:1em">
				<div style="width:34%;float:left;">
					Filename filter:
					<input name="filt" onKeypress="event.cancelBubble=true;" onkeyup="filter(this)" type="text" class="input"/>
				</div>
				<div style="width:65%;float:right;">
					Current Location: {CurrentLocation}
				</div>
			</div>
			<table id="filetable" class="table striped padding border-top" cellpadding="0" cellspacing="0">
				<tr>
					<th></th>
					<th>Name</th>
					<th>Size</th>
					<th>Type</th>
					<th>Date</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
				{Rows}
				<tr >
					<td colspan="7">
						<input type="checkbox" id="selall" name="selall" onClick="selectAll(this.form)">
						<label for="selall">Select All</label>
					</td>
				</tr>
			</table>
			<div class="border-top">
			<p>
			<input type="text" name="dir" value="{Dir}" style="display:none"/>
			<input title="Delete selected files and directories."  class="button red" type="Submit" onclick="return confirm('Are you sure want to delete selected files');" name="button" value="Delete Selected Files">
			<!--input title="Download selected files and directories as one zip file"  id="but_Zip" type="Submit" name="Submit" value="Download selected files as zip"-->
			</p>
			<p>
			Current Location: {CurrentLocation}
				
			</p>
			<p>
			<input type="text" name="createfile" class="input"/>
			<input title="Create directory." class="button blue" type="Submit" name="button" value="Create Directory">
			&nbsp;
			<input title="Create File."  class="button blue" type="Submit" name="button" value="Create File">
			</p>
			</div>
		</form>
	</div>