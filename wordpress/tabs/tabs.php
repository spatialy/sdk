<?php

namespace plainview\wordpress\tabs;

/**
	@brief		Handles creation of tabs in the Wordpress admin panel.

	@par		Changelog

	- 20130810	The current tab's link is cleaned.
	- 20130809	Countable.
	- 20130530	get() and get_key() added.
	- 20130506	output() changed to render()
	- 20130503	Initial release

	@author		Edward Plainview	edward@plainview.se
	@since		20130503
	@version	20130810
**/

class tabs
	implements \Countable
{
	/**
		@brief		\\plainview\\wordpress\\base object that created these tabs.
		@since		20130503
		@var		$base
	**/
	public $base;

	/**
		@brief		_GET variable to use. The default is the actual _GET.
		@since		20130503
		@since		20130503
		@var		$get
	**/
	public $get;

	/**
		@brief		Which key in the _GET variable contains the ID of the current tab.
		@since		20130503
		@since		20130503
		@var		$get_key
	**/
	public $get_key = 'tab';

	/**
		@brief		The ID of the default tab, if none is selected in the _GET.
		@details	If no default tab is set, the first added tab is assumed to be the default.
		@since		20130503
		@since		20130503
		@var		$default_tab
	**/
	public $default_tab = '';

	/**
		@brief		Display the selected tab?
		@since		20130503
		@var		$display_tab
	**/
	public $display_tab = true;

	/**
		@brief		Display the name / heading of the selected tab?
		@since		20130503
		@var		$display_tab_name
	**/
	public $display_tab_name = true;

	/**
		@brief		The default prefix of the displayed tab name.
		@details	The tab inherits this value upon creation.
		@since		20130503
		@var		$tab_prefix
	**/
	public $tab_prefix = '<h2>';

	/**
		@brief		The default suffix of the displayed tab name.
		@details	The tab inherits this value upon creation.
		@since		20130503
		@var		$tab_suffix
	**/
	public $tab_suffix = '</h2>';

	/**
		@brief		Array of \\plainview\\wordpress\\tabs\\tab objects.
		@since		20130503
		@var		$tabs
	**/
	public $tabs = array();

	/**
		@brief		Array of _GET keys to preserve when creating tab links.
		@since		20130503
		@var		$valid_get_keys
	**/
	public $valid_get_keys = array( 'page' );

	public function __construct( $base )
	{
		$this->base = $base;
	}

	public function __toString()
	{
		return $this->render();
	}

	/**
		@brief		Return how many tabs are registered.
		@return		int		The count of tabs registered.
		@since		20130809
	**/
	public function count()
	{
		return count( $this->tabs );
	}

	/**
		@brief		Sets the current tab.
		@param		string		$id				ID of tab to make the default.
		@return		tabs						Object chaining.
		@since		20130503
	**/
	public function default_tab( $id )
	{
		$this->default_tab = $id;
		return $this;
	}

	/**
		@brief		Sets the _GET array.
		@param		array		$get			The new _GET array from which to get the current tab.
		@return		tabs						Object chaining.
		@since		20130530
	**/
	public function get( $get )
	{
		$this->get = $get;
		return $this;
	}

	/**
		@brief		Sets the get key.
		@param		string		$get_key		New key for the _GET array.
		@return		tabs						Object chaining.
		@since		20130530
	**/
	public function get_key( $get_key )
	{
		$this->get_key = $get_key;
		return $this;
	}

	/**
		@brief		Return the tabs.
		@details	Although the tabs can be displayed using __toString, this method allows for finding and catching exceptions, which isn't allowed in __toString.
		@return		string		The tabs as a string.
		@since		20130503
	**/
	public function render()
	{
		if ( $this->get === null )
			$this->get = $_GET;
		$get = $this->get;					// Conv
		$get_key = $this->get_key;			// Conv

		// No tabs? Do nothing.
		if ( count( $this->tabs ) < 1 )
			return '';

		// Check that the default exists.
		if ( ! is_object( $this->tab( $this->default_tab ) ) )
			$this->default_tab = key( $this->tabs );

		// Select the default tab if none is selected.
		if ( ! isset( $get[ $get_key ] ) )
			$get[ $get_key ] = $this->default_tab;
		$selected = $get[ $get_key ];

		$r = '';

		if ( count( $this->tabs ) > 1 )
		{
			// Step 1: display all the tabs
			$r .= '<ul class="subsubsub">';
			$original_link = $_SERVER['REQUEST_URI'];

			foreach( $get as $key => $value )
				if ( ! in_array( $key, $this->valid_get_keys ) )
					$original_link = remove_query_arg( $key, $original_link );

			$counter = 1;
			foreach( $this->tabs as $tab_id => $tab )
			{
				// Make the link.
				// If we're already on that tab, just return the current url.
				if ( $get[ $get_key ] == $tab_id )
					$link = add_query_arg( $get_key, $tab_id, $original_link );
				else
				{
					if ( $tab_id == $this->default_tab )
						$link = remove_query_arg( $get_key, $original_link );
					else
						$link = add_query_arg( $get_key, $tab_id, $original_link );
				}

				$text = $tab->name;

				if ( $tab->count != '' )
					$text .= sprintf( ' <span class="count">%s</span>', $tab->count );

				$separator = ( $counter < count( $this->tabs ) ? '&nbsp;|&nbsp;' : '' );
				$current = ( $tab_id == $selected ? ' class="current"' : '' );

				$title = '';
				if ( $tab->title != '' )
					$title = sprintf( ' title="%s"', $tab->title );

				$r .= sprintf( '<li><a%s%s href="%s">%s</a>%s</li>',
					$current,
					$title,
					$link,
					$text,
					$separator
				);
				$counter++;
			}
			$r .= '</ul>';
		}

		// Step 2: maybe display the tab itself
		if ( $this->display_tab )
		{
			$tab = $this->tab( $selected );
			ob_start();
			echo '<div class="wrap">';
			if ( $this->display_tab_name )
			{
				$name = ( $tab->heading != '' ? $tab->heading : $tab->name );
				echo $tab->prefix . $name . $tab->suffix;
			}

			echo $r;
			echo '<div style="clear: both"></div>';

			call_user_func_array( $tab->callback, $tab->parameters );

			echo '</div>';
			$r = ob_get_clean();
		}

		return $r;
	}

	/**
		@brief		Creates a new tab / retrieves an existing tab.
		@param		string		$id		ID of tab to create / retrieve.
		@return		tab					Tab object.
		@since		20130503
	**/
	public function tab( $id )
	{
		if ( $id == '' )
			return false;
		if ( isset( $this->tabs[ $id ] ) )
			return $this->tabs[ $id ];
		$tab = new tab( $this );
		$tab->id = $id;
		$tab->callback_this( $id );		// Usually the tab's callback is the same as the ID.
		$this->tabs[ $id ] = $tab;
		return $this->tabs[ $id ];
	}
}

