
function show( id, html ) {
	return modal.show( id, html );
}

function hide( id ) {
	return modal.hide( id );
}

function sleep( ms ) {
	return new Promise(resolve => setTimeout( resolve, ms ));
}

document.addEventListener( 'click', (e) =>
{
	ModalBlueprint.backdrop_listener(e, 'click');
});

document.addEventListener( 'keyup', (e) =>
{
	switch ( e.key )
	{
		case "Escape":
			ModalBlueprint.backdrop_listener(e, 'keyup');
			break;
	}
});