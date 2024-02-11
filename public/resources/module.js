class ModalBlueprint
{
	config =
		{
			gap: {
				status: false,
				x: 10,				//	percentage
				y: 10				//	percentage
			},
			autoClose: {
				status: false,
				timeInterval: 0
			},
			entryDelay: {
				status: false,
				timeInterval: 0
			},
			css: {
				"width": "0",
				"max-width": "90%",
				"max-height": "90%",
				"background": "white",
				"box-sizing": "border-box",
				"border-radius": "5px",
				"transition": ".3s",
				"z-index": "10000000",
				"overflow": "auto"
			},
			cssProperty: [],
			backdrop: false,
			transparent: false,
			identifier: null,
			container: null,
			parent: null,
			position: "center-center",
		};
	
	constructor( id, html )
	{
		if ( this.validate_element( id ) )
		{
			console.log( `Element identifier must be unique, given ID attribute value is invalid (${id})` );
			return false;
		}
		else {
			this.config.identifier = modal.prefix + id;
			
			if ( html.charAt(0) === "#" )
			{
				let identifier = html.replace( "#", '' ).trim();
				
				if ( this.validate_element( identifier ) ) {
					this.config.container = document.getElementById( identifier ).innerHTML;
				}
				else this.config.container = html;
			}
			else this.config.container = html;
		}
		
		this.config.gap = {
		
		}
	}
	
	async start()
	{
		this.create_transparent();
		this.create_modal();
		this.prepare_modal();
		await this.entry_delay();
		await this.display_modal();
		this.auto_close();
	}
	
	async entry_delay()
	{
		if ( this.config.entryDelay.status ) {
			await sleep( this.config.entryDelay.timeInterval );
		}
	}
	
	async display_modal()
	{
		await sleep( 50 );
		let style = this.config.parent.style;
		
		style.visibility = "visible";
		switch ( this.config.position )
		{
			case 'right-top':
				style.right	= this.config.gap.status ? `${this.config.gap.x}%` : `1%`;
				break;
			
			case 'right-bottom':
				style.right	= this.config.gap.status ? `${this.config.gap.x}%` : `1%`;
				break;
			
			case 'left-top':
				style.left = this.config.gap.status ? `${this.config.gap.x}%` : `1%`;
				break;
			
			case 'left-bottom':
				style.left = this.config.gap.status ? `${this.config.gap.x}%` : `1%`;
				break;
			
			case 'center-top':
				style.top = this.config.gap.status ? `${this.config.gap.y}%` : `5%`;
				break;
			
			case 'center-bottom':
				style.bottom = this.config.gap.status ? `${this.config.gap.y}%` : `5%`;
				break;
			
			case 'center-center':
				style.transition = this.config.css.transition;
				style.transform = "translate(-50%, -50%) scale(1)";
				break;
		}
	}
	
	static backdrop_listener(e, type)
	{
		if ( Object.keys( modal.backdrop ).length )
		{
			for ( const name in modal.backdrop )
			{
				const element 	 = document.getElementById( name );
				const identifier = name.replace( modal.prefix, "" );
				
				switch ( type )
				{
					case 'keyup':
						delete modal.backdrop[ name ];
						modal.hide( identifier );
						break;
					
					case 'click':
						if ( element !== null && !element.contains( e.target ) )
						{
							delete modal.backdrop[ name ];
							modal.hide( identifier );
							break;
						}
						break;
				}
			}
		}
	}
	
	prepare_modal()
	{
		let style = this.config.parent.style;
		let height = this.config.parent.offsetHeight;
		let width = this.config.parent.offsetWidth;
		
		switch ( this.config.position )
		{
			case 'right-top':
				style.right 	= 	`-${modal.hidden + width}px`;
				style.top 		= 	this.config.gap.status ? `${this.config.gap.y}%` : `7%`;
				style.bottom 	=	'unset';
				style.left 		=	'unset';
				break;
			
			case 'right-bottom':
				style.right		=	`-${modal.hidden + width}px`;
				style.bottom 	= 	this.config.gap.status ? `${this.config.gap.y}%` : `7%`;
				style.top 		=	'unset';
				style.left 		=	'unset';
				break;
			
			case 'left-top':
				style.left 		= 	`-${modal.hidden + width}px`;
				style.top		=	this.config.gap.status ? `${this.config.gap.y}%` : `7%`;
				style.bottom 	=	'unset';
				style.right 	=	'unset';
				break;
			
			case 'left-bottom':
				style.left 		= 	`-${modal.hidden + width}px`;
				style.bottom	=	this.config.gap.status ? `${this.config.gap.y}%` : `7%`;
				style.right 	=	'unset';
				style.top 		=	'unset';
				break;
			
			case 'center-top':
				style.top			=	`-${modal.hidden + height}px`;
				style.left 			= 	`0`;
				style.right 		= 	`0`;
				style.bottom 		= 	`unset`;
				style.marginLeft 	= 	`auto`;
				style.marginRight 	= 	`auto`;
				style.marginTop		= 	`unset`;
				break;
			
			case 'center-bottom':
				style.bottom 		= 	`-${modal.hidden + height}px`;
				style.left 			= 	`0`;
				style.right 		= 	`0`;
				style.top 			= 	`unset`;
				style.marginLeft 	= 	`auto`;
				style.marginRight 	= 	`auto`;
				style.marginBottom	= 	`unset`;
				break;
			
			case 'center-center':
				style.top 			= 	`50%`;
				style.left 			= 	`50%`;
				style.transform 	= 	`translate(-50%, -50%) scale(0)`;
				style.transition 	= 	`unset`;
				break;
		}
	}
	
	auto_close()
	{
		if ( this.config.autoClose.status )
		{
			if ( modal.running[ this.config.identifier ] != null && modal.running[ this.config.identifier ] !== undefined )
			{
				clearTimeout( modal.running[ this.config.identifier ] );
			}
			
			modal.running[ this.config.identifier ] = setTimeout(() =>
				{
					modal.hide( this.config.identifier.replace( modal.prefix, "" ) );
					modal.running[ this.config.identifier ] = null;
				},
				this.config.autoClose.timeInterval );
			
		}
	}
	
	create_modal()
	{
		let name = this.config.identifier;
		let elem = document.getElementById( name );
		
		if ( this.validate_element( name ) === false )
			elem = document.createElement( "div" );
		else
			elem.innerHTML = "";
		
		for ( let i = 0; i < this.config.cssProperty.length; i++ ) {
			elem.classList.add( this.config.cssProperty[i] );
		}
		
		elem.setAttribute( 'id', 		name );
		elem.setAttribute( 'position', 	this.config.position );
		elem.setAttribute( 'style', 	Object.entries( this.config.css ).map(([ property, value ]) => `${property}: ${value}`).join("; ") );
		
		elem.innerHTML 			= 	this.config.container;
		elem.style.width 		= 	`fit-content`;
		elem.style.height 		= 	`auto`;
		elem.style.position 	= 	`fixed`;
		elem.style.visibility	=	`hidden`;
		this.config.parent 		= 	elem;
		
		document.body.appendChild( this.config.parent );
		
		if ( this.config.backdrop ) {
			modal.backdrop[ name ] = {
				status: true,
				preliminary: false
			};
		}
	}
	
	create_transparent()
	{
		if ( this.config.transparent )
		{
			const name = `${this.config.identifier}-transparent`;
			if ( this.validate_element( name ) === false )
			{
				let element = document.createElement( 'div' );
				
				element.classList.add( 'modal-transparent' );
				element.setAttribute( "id", name );
				element.style.zIndex = ( parseInt( this.config.css[ "z-index" ] ) - 1 ).toString();
				
				document.body.appendChild( element );
			}
		}
	}
	
	validate_element( id )
	{
		let element = document.getElementById( id );
		if ( typeof( element ) != 'undefined' && element != null ) {
			return true;
		}
		
		return false;
	}
}

class modal
{
	static prefix = 'popup-';
	static hidden = 250;
	static running = {};
	static backdrop = {};
	
	static show( id, html )
	{
		let object	=	new ModalBlueprint( id, html );
		let config 	= 	{
			setBackDrop: ( opt = true ) => {
				object.config.backdrop = opt;
				object.config.transparent = opt ? true : object.config.transparent;
				return config;
			},
			setDelay: ( ms = 1000 ) => {
				object.config.entryDelay.status = true;
				object.config.entryDelay.timeInterval = ms;
				return config;
			},
			setGap: ( gap = { x: 20, y: 20 } ) => {
				object.config.gap.status = true;
				object.config.gap.x = gap.x ?? object.config.gap.x;
				object.config.gap.y = gap.y ?? object.config.gap.y;
				return config;
			},
			setPosition: ( position = "center-center" ) => {
				object.config.position = position;
				return config;
			},
			setCSS: ( styles ) => {
				
				if ( Array.isArray( styles ) )
				{
					if ( styles.every( obj => typeof obj === 'object' && obj !== null) ) {
						object.config.css = { ...object.config.css, ...styles };
					}
					else
					{
						for ( let i = 0; i < styles.length; i++ ) {
							object.config.cssProperty.push( styles[ i ] );
						}
					}
				}
				
				return config;
			},
			setTransparent:( opt = true ) => {
				object.config.transparent = object.config.backdrop ? true : opt;
				return config;
			},
			setAutoClose: ( ms = 2000 ) => {
				object.config.autoClose.status = true;
				object.config.autoClose.timeInterval = ms;
				return config;
			}
		};
		
		setTimeout(  () => object.start(), 50 );
		return config;
	}
	
	static hide( id )
	{
		let name 	= 	modal.prefix + id;
		let elem 	= 	document.getElementById( name );
		let trans 	= 	document.getElementById( `${name}-transparent` );
		let pos 	=	elem.getAttribute( 'position' );
		let rem 	=	elem.getBoundingClientRect();
		let style 	= 	elem.style;
		
		delete modal.backdrop[ name ];
		style.transition = ".450s";
		
		switch ( pos )
		{
			case 'right-top':
				style.right = `-${ modal.hidden + rem.width }px`;
				break;
			
			case 'right-bottom':
				style.right = `-${ modal.hidden + rem.width }px`;
				break;
			
			case 'left-top':
				style.left = `-${ modal.hidden + rem.width }px`;
				break;
			
			case 'left-bottom':
				style.left = `-${ modal.hidden + rem.width }px`;
				break;
			
			case 'center-top':
				style.top =	`-${ modal.hidden + rem.height }px`;
				break;
			
			case 'center-bottom':
				style.bottom = `-${ modal.hidden + rem.height }px`;
				break;
			
			case 'center-center':
				style.transition = ".3s";
				style.transform = "translate(-50%, -50%) scale(0)";
				break;
		}
		
		if ( trans ) {
			trans.style.opacity = "0";
			setTimeout( () => trans.remove(), 500 );
		}
		setTimeout( () => elem.remove(), 460 );
	}
}

