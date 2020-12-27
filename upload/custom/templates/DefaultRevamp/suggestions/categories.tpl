<div class="ui segment">
  <h4>Categories</h4>
  <hr>
  <div class="ui grid">
	{foreach from=$CATEGORIES_LIST item=item}
	  <div class="eight wide column">
	    <a href="{$item.link}">{$item.name}</a>
	  </div>
	{/foreach}
  </div>
</div>