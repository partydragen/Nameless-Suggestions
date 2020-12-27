{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui container" style="padding-bottom:300px;">
  <div class="ui segment">
	<h2 style="display:inline;">Editing Suggestion</h2>
	<hr>
				
	{if isset($ERRORS)}
	  <div class="ui negative icon message">
		<i class="x icon"></i>
		<div class="content">
		  {foreach from=$ERRORS item=error}
			{$error}<br />
		  {/foreach}
		</div>
	  </div>
	{/if}	
		 
	<form class="ui large form" action="" method="post">
	  <div class="field">
	    <label for="titleLabel">Title <span style="color:red"><strong>*</strong></span></label>
		<input type="text" name="title" placeholder="Title" value="{$TITLE}">
	  </div>
	  <div class="field">
	    <label for="categoryLabel">Category <span style="color:red"><strong>*</strong></span></label>
		<select name="category" id="category">
		  {foreach from=$CATEGORIES item=item}
		    <option value="{$item.id}" {if $CATEGORY == $item.id}selected{/if}>{$item.name}</option>
		  {/foreach}
		</select>
	  </div>
	  <div class="field">
	    <label for="statusLabel">Status </label>
		<select name="status" id="status">
		  {foreach from=$STATUSES item=item}
		    <option value="{$item.id}" {if $STATUS == $item.id}selected{/if}>{$item.name}</option>
		  {/foreach}
		</select>
	  </div>
	  <div class="field">
	    <label for="contentLabel">Content <span style="color:red"><strong>*</strong></span></label>
		<textarea style="width:100%" name="content" placeholder="Content" id="editor" rows="15">{$CONTENT}</textarea>
	  </div>
	  <input type="hidden" name="token" value="{$TOKEN}">
	  <input type="submit" class="ui primary button" value="Submit">
	  <a class="ui negative button" href="{$CANCEL_LINK}">{$CANCEL}</a>
	</form>
  </div>  
</div>

{include file='footer.tpl'}