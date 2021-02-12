{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui container" style="padding-bottom:300px;">
  <div class="ui segment">
	<div class="ui stackable grid">
	  <div class="ui centered row">
		<div class="ui ten wide tablet twelve wide computer column">
		  <h1 style="display:inline;">{$SUGGESTIONS}</h1><span class="right floated">
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
		  
		  <div class="ui segment">
			<h3 style="display:inline;">{$TITLE}</h3></br></br>
			<a href="{$POSTER_PROFILE}" class="{$POSTER_TAG}" style="{$POSTER_STYLE}" target="_blank"><img src="{$POSTER_AVATAR}" class="ui mini avatar image" style="max-height:25px;max-width:25px;" alt="{$POSTER_USERNAME}" /> {$POSTER_USERNAME}</a>:
				  <span class="right floated" data-toggle="tooltip" data-original-title="{$POSTER_DATE}">{$POSTER_DATE_FRIENDLY}</span>
			
			<hr>
			{$CONTENT}
			<hr>
			
			<form style="display:inline;" action="" method="post">
			  <input type="hidden" name="action" value="vote">
			  <input type="hidden" name="token" value="{$TOKEN}">
			  <input type="hidden" name="vote" value="1">
			  <a href="#" onclick="$(this).closest('form').submit();" style="padding:10px;{if {$VOTED} == 1} color:green;{/if}" rel="tooltip" data-content="Like"><i class="fa fa-thumbs-up"></i> {$LIKES}</a>
			</form>
			<form style="display:inline;" action="" method="post">
			  <input type="hidden" name="action" value="vote">
			  <input type="hidden" name="token" value="{$TOKEN}">
			  <input type="hidden" name="vote" value="2">
			  <a href="#" onclick="$(this).closest('form').submit();" style="padding:10px;{if {$VOTED} == 2} color:green;{/if}" rel="tooltip" data-content="Dislike"><i class="fa fa-thumbs-down"></i> {$DISLIKES}</a>
			</form>
			
			{if isset($CAN_MODERATE)}
			<span class="right floated">
				<a class="ui small yellow icon button" data-toggle="tooltip" data-content="Edit" href="/suggestions/edit/?sid={$ID}"><i class="fas fa-pencil-alt fa-fw" aria-hidden="true"></i></a>
				<button class="ui small red icon button" rel="tooltip" data-content="Delete" onclick="$('#deleteModal').modal('show');"><i class="fas fa-trash fa-fw" aria-hidden="true"></i></button>
			</span>
			{/if}
		  </div>
			
            <h4>{$COMMENTS_TEXT}</h4>
		    {if count($COMMENTS_LIST)}
			  {foreach from=$COMMENTS_LIST item=comment}
               <div class="ui segment">
				  <a href="{$comment.profile}" class="{$comment_tag}" style="{$comment.style}" target="_blank"><img src="{$comment.avatar}" class="ui mini avatar image" style="max-height:25px;max-width:25px;" alt="{$comment.username}" /> {$comment.username}</a>:
				  <span class="right floated" data-toggle="tooltip" data-original-title="{$comment.date}">{$comment.date_friendly}</span>
				  <hr>
				  {$comment.content}
                </div>
			  {/foreach}
		    {else}
			  {$NO_COMMENTS}
		    {/if}
			
			{if isset($CAN_COMMENT)}
		    </br>
		    <form class="ui form" action="" method="post">
			  {if isset($CAN_MODERATE)}
			  <div class="field">
				<label for="statusLabel">Status </label>
				<select name="status" id="status">
				  {foreach from=$STATUSES item=item}
					<option value="{$item.id}" {if $STATUS == $item.id}selected{/if}>{$item.name}</option>
				  {/foreach}
				</select>
			  </div>
			  {/if}
              <div class="field">
                <textarea name="content" rows="5" placeholder="{$NEW_COMMENT}"></textarea>
			  </div>
			 <input type="hidden" name="action" value="comment">
             <input type="hidden" name="token" value="{$TOKEN}">
             <input type="submit" value="{$SUBMIT}" class="ui primary button">
		    </form>
			{/if}
		  
		</div>
		
		<div class="ui six wide tablet four wide computer column">
		  {include file='suggestions/search.tpl'}
		  {include file='suggestions/recent_activity.tpl'}
		</div>
		
	  </div>
	</div>
  </div>
</div>

<!-- Suggestion deletion modal -->
<div class="ui tiny modal" id="deleteModal">
  <div class="header">Confirm deletion</div>
  <div class="content">
	<p>Are you sure you want to delete this suggestion?</p>
  </div>
  <div class="actions">
	<form action="" method="post">
	  <button type="button" class="ui yellow button" onclick="$('#deleteModal').modal('hide');">Cancel</button>
	  <input type="hidden" name="action" value="deleteSuggestion">
	  <input type="hidden" name="token" value="{$TOKEN}">
	  <input type="submit" class="ui red button" value="Delete">
	</form>
  </div>
</div>

{include file='footer.tpl'}
