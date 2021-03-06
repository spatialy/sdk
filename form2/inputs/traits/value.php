<?php

namespace plainview\form2\inputs\traits;

/**
	@brief		Value manipulation.
	@details	Values are saved safe.

	If you wish to retrieve the value, run a \plainview\form2\form::unfilter_text() on it to return it to its original status.

	@author		Edward Plainview <edward@plainview.se>
	@copyright	GPL v3
	@version	20130814
**/
trait value
{
	public $value;
	public $value_filters = [];

	/**
		@brief		Adds a value filter.
		@details

		Value filters are functions that filter the value before setting it.

		The $name parameter can either be a string containing the name of the function ("number" will call value_filter_number) or an array of [ class, function ].

		@param		string		$name		Brief name of the filter function. The method value_filter_$name is called.
		@return		$this		Method chaining.
		@see		apply_value_filters()
		@see		remove_value_filter()
		@since		20130712
	**/
	public function add_value_filter( $function )
	{
		if ( is_array( $function ) )
			$name = get_class( $function[0] );
		else
			$name = $function;
		$this->value_filters[ $name ] = $function;
		return $this;
	}

	/**
		@brief		Apply all value filters on this value.
		@param		string		$value		Value to filter.
		@return		string		The filtered value.
		@see		add_value_filter()
		@see		remove_value_filter()
		@since		20130712
	**/
	public function apply_value_filters( $value )
	{
		foreach( $this->value_filters as $name )
		{
			if ( is_array( $name ) )
				$callable = $name;
			else
				$callable = [ $this, 'value_filter_' . $name ];
			$value = call_user_func( $callable, $value );
		}
		return $value;
	}

	/**
		@brief		Exists solely to be overriden by those inputs that have the value in between their tags.
		@return		string		Usually nothing, but some input types have their value between their tags and not as element attributes.
		@since		20130524
	**/
	public function display_value()
	{
		return '';
	}

	/**
		@brief		Returns the input's filtered value from the _POST variable.
		@details	Strips off dangerous code.
		@return		string		The filtered value of the _POST variable. If no value was in the post, null is returned.
		@see		get_post_value()
		@since		20130724
	**/
	public function get_filtered_post_value()
	{
		$value = $this->get_post_value();
		return \plainview\form2\form::filter_text( $value );
	}

	/**
		@brief		Returns the input's value from the _POST variable.
		@details	Will strip off slashes before returning the value.
		@return		string		The value of the _POST variable. If no value was in the post, null is returned.
		@see		get_filtered_post_value()
		@see		use_post_value()
		@since		20130524
	**/
	public function get_post_value()
	{
		$value = $this->form()->get_post_value( $this->make_name() );
		if ( $value !== null )
			$value = stripslashes( $value );
		$value = $this->apply_value_filters( $value );
		return $value;
	}

	/**
		@brief		Returns the current value.
		@details	Note that the value has been filtered.
		@return		string		The current value of the input.
		@see		set_value()
		@see		value()
		@since		20130524
	**/
	public function get_value()
	{
		return $this->get_attribute( 'value' );
	}

	/**
		@brief		Removes a value filter.
		@param		string		$name		Brief name of the filter function.
		@see		add_value_filter()
		@see		apply_value_filters()
		@since		20130712
	**/
	public function remove_value_filter( $name )
	{
		if ( isset( $this->value_filters[ $name ] ) )
			unset( $this->value_filters[ $name ] );
		return $this;
	}

	/**
		@brief		Sets the post value of this input.
		@return		this		Method chaining.
		@see		use_post_value()
		@since		20130712
	**/
	public function set_post_value( $value )
	{
		$value = $this->apply_value_filters( $value );
		$this->form()->set_post_value( $this->make_name(), $value );
		return $this;
	}

	/**
		@brief		Filters and sets the new value.
		@param		string		$value		Value to filter and set.
		@return		this		Object chaining.
		@see		get_value()
		@see		value()
		@since		20130524
	**/
	public function set_value( $value )
	{
		$value = $this->apply_value_filters( $value );
		$value = \plainview\form2\form::filter_text( $value );
		$this->set_attribute( 'value', $value );
		return $this;
	}

	/**
		@brief		Retrieve this input's value from the _POST.
		@details	Will set the input's value to whatever is in the POST.
		@return		this		Object chaining.
		@see		get_post_value()
		@since		20130524
	**/
	public function use_post_value()
	{
		// Disabled and readonlys will not find their values in the _POST.
		if ( $this->is_readonly() || $this->is_disabled() )
			return $this;
		$value = $this->get_post_value();
		$value = $this->apply_value_filters( $value );
		$this->value( $value );
		return $this;
	}

	/**
		@brief		Convenience function for setting the value.
		@param		string		The new value to filter and set.
		@see		get_value()
		@see		set_value()
		@since		20130524
	**/
	public function value( $value )
	{
		$value = $this->apply_value_filters( $value );
		return $this->set_value( $value );
	}
}
