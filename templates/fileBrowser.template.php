	<div class="container">
		<div>{Message}</div>
		<div class="border padding margin-top margin-bottom">
			<form action="file-browser.php" method="POST" enctype="multipart/form-data">
				<p><input type="text" name="dir" value="{Dir}" style="display:none"/>
				<input type="file" onKeypress="event.cancelBubble=true;" name="myfile">
				<input title="Upload selected file to the current working directory" type="Submit"  name="Submit" value="Upload"/></p>
				<p>
				<input type="button" name="button" value="Launch Shell Program"  onclick="window.location = '?cmd=ssh'">
				</p>
			</form>
		</div>
		
		<form action="" method="Post" name="filelist">
			<div>
				<div style="width:34%;float:left;">
					Filename filter:
					<input name="filt" onKeypress="event.cancelBubble=true;" onkeyup="filter(this)" type="text">
				</div>
				<div style="width:65%;float:right;">
					Current Location: {CurrentLocation}
				</div>
			</div>
			<table id="filetable" class="table striped padding" cellpadding="0" cellspacing="0">
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
			<br/>
			<p>
			<input type="text" name="dir" value="{Dir}" style="display:none"/>
			<input title="Delete selected files and directories."  type="Submit" onclick="return confirm('Are you sure want to delete selected files');" name="button" value="Delete Selected Files">
			<!--input title="Download selected files and directories as one zip file"  id="but_Zip" type="Submit" name="Submit" value="Download selected files as zip"-->
			</p>
			<p>
			Current Location: {CurrentLocation}
				
			</p>
			<p>
			<input type="text" name="createfile">
			<input title="Create directory."  type="Submit" name="button" value="Create Directory">
			&nbsp;
			<input title="Create File."  type="Submit" name="button" value="Create File">
			</p>
		</form>
	</div>