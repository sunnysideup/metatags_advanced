	<% loop MyRecords %>
	<tr class="$FirstLast $EvenOdd" id="TR-$ID">

		<td class="filename">
		<% if ParentSegments %>
			<% loop ParentSegments %>
				<% if Last %>
				<% if ClassName = Folder %>
				<strong><a href="/admin/assets/show/$ID/" class="newWindow">$URLSegment</a></strong>
				<% else %>
				<strong><a href="/admin/assets/EditForm/field/File/item/$ID/edit" class="newWindow">$URLSegment</a></strong>
				<% end_if %>

				<% else %>
				<a href="$Link" title="File Type: $ClassName, Title: $Title  - click to open pages on this level" class="goOneUpLink" rel="TR-$ID">$FilenameSegment/</a>
				<% end_if %>
			<% end_loop %>
		<% end_if %>
			<% if ClassName = Folder %>
				<% if ChildrenLink %><a href="$ChildrenLink" class="goOneDownLink" title="go down one level and view child pages of: $Name.ATT" rel="TR-$ID">++++++++++=</a><% end_if %>
				<div class="iconHolder"><img src="/metatags_advanced/images/Folder.png" alt="$ClassName" class="defaultIcon" /></div>
				<div class="fileInfo">
			<% else %>
				<% if CMSThumbnail %>
					<div class="iconHolder"><a href="$Link" class="newWindow bold">$CMSThumbnail</a></div>
				<% end_if %>
				<% if Error %>
					<div class="errorHolder">ERROR: $Error</div>
				<% end_if %>
				<div class="fileInfo">
					<% if getFileType %><br /><span class="label">Type:</span> <span class="data">$getFileType</span><% end_if %>
					<% if getSize %><br /><span class="label">Size:</span> <span class="data">$getSize</span><% end_if %>
					<% if getDimensions %><br /><span class="label">Dimensions:</span> <span class="data">$getDimensions</span><% end_if %>
			<% end_if %>
					<div class="usage">
			<% if UsageCount %>
						<span class="label">Used:</span> <span class="data">$UsageCount time(s)</span>
						<% if ListOfPlaces.Count %><ul><% loop ListOfPlaces %>
							<li><% if Link %><a href="$Link">$Title</a><% else %>$ClassName ($Title)<% end_if %></li>
						<% end_loop%></ul><% end_if %>
			<% else %>
					<!-- a href="$RecycleLink" class=" ajaxify" not used on the site: img src="metatags_advanced/images/recycle.png" alt="Recycle" title="Recycle"  -->
			<% end_if %>
					</div>
				</div>
		</td>

		<td class="title">
			<span class="highRes">
				<textarea type="text" id="Title_{$ID}" name="Title_{$ID}" rows="2" colspan="20" disabled="disabled">$Title</textarea>
			</span>
		</td>

		<td class="content">
			<span>
				<textarea rows="2" cols="20" id="Content_{$ID}" name="Content_{$ID}" disabled="disabled">$Content</textarea>
			</span>
		</td>

	</tr>
	<% end_loop %>
