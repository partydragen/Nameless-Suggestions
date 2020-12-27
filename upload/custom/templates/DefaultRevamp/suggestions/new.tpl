{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui container" style="padding-bottom:300px;">
  <div class="ui segment">
	<h1 style="display:inline;">{$NEW_SUGGESTION}</h1><span class="right floated">
		  <a class="ui small negative button" href="{$BACK_LINK}">{$BACK}</a></span>
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
		<div class="ui left aligned category search">
	    <label for="titleLabel">Title <span style="color:red"><strong>*</strong></span></label>
		<input class="prompt" type="text" name="title" placeholder="Title" value="{$TITLE}">
		<div class="results"></div>
		</div>
		
	  </div>
	  <div class="field">
	    <label for="categoryLabel">Category <span style="color:red"><strong>*</strong></span></label>
		<select name="category" id="category">
		  {foreach from=$CATEGORIES item=item}
		    <option value="{$item.id}">{$item.name}</option>
		  {/foreach}
		</select>
	  </div>
	  <div class="field">
	    <label for="contentLabel">Content <span style="color:red"><strong>*</strong></span></label>
		<textarea style="width:100%" name="content" placeholder="Content" id="editor" rows="15">{$CONTENT}</textarea>
	  </div>
	  <input type="hidden" name="token" value="{$TOKEN}">
	  <input type="submit" class="ui primary button" value="Submit">
	</form>
  </div>  
</div>

<script>
$(document)
	.ready(function() {
		// create sidebar and attach to menu open
		$('.ui.sidebar')
			.sidebar('attach events', '.toc.item')
		;
	});
	
	{literal}
	$('.ui.search')
	  .search({
		type: 'category',
		apiSettings: {
		  url: '/knowledgebase/search_api/?q={query}'
		},
		minCharacters: 3
	  })
	;
	{/literal}
</script>

{include file='footer.tpl'}