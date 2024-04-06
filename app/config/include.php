<?php
	# Remember this should be [ORDERED] base on its parent class
	return
	[
		/* ARTISAN */
		'artisan'	=> 	dirname( __DIR__ ). '/config/artisan/artisan.php',
		
		/* SCHEMA */
		'sql'		=>	dirname( __DIR__ ). '/config/schema/SQL.php',
		'structure'	=>	dirname( __DIR__ ). '/config/schema/structure.php',
		'blueprint'	=>	dirname( __DIR__ ). '/config/schema/blueprint.php',
		'migration'	=>	dirname( __DIR__ ). '/config/schema/migration.php',
		'seeds'		=>	dirname( __DIR__ ). '/config/schema/seeder.php',
		'schema'	=>	dirname( __DIR__ ). '/config/schema/schema.php',
		
		/* MODEL */
		'reader'	=>	dirname( __DIR__ ). '/config/model/reader.php',
		'execution'	=>	dirname( __DIR__ ). '/config/model/execution.php',
		'eloquent'	=>	dirname( __DIR__ ). '/config/model/eloquent.php',
		'model'		=>	dirname( __DIR__ ). '/config/model/model.php',
		
		/* DATABASE */
		'layer'		=>	dirname( __DIR__ ). '/config/connector/layer.php',
		'db_facade'	=>	dirname( __DIR__ ). '/config/connector/facade.php',
		'query'		=>	dirname( __DIR__ ). '/config/connector/db.php',
		'mysqli'	=>	dirname( __DIR__ ). '/config/connector/_mysqli.php',
		'pdo'		=>	dirname( __DIR__ ). '/config/connector/_pdo.php',
		'binds'		=>	dirname( __DIR__ ). '/config/connector/binds.php',
		
		/* ROUTES */
		'facade'	=>	dirname( __DIR__ ). '/config/routes/facade.php',
		'buffer'	=>	dirname( __DIR__ ). '/config/routes/buffer.php',
		'actions'	=>	dirname( __DIR__ ). '/config/routes/actions.php',
		'route'		=>	dirname( __DIR__ ). '/config/routes/route.php',
		'router'	=>	dirname( __DIR__ ). '/config/routes/router.php',
		'compiler'	=>	dirname( __DIR__ ). '/config/routes/compiler.php',
		'dispatch'	=>	dirname( __DIR__ ). '/config/routes/dispatch.php',
		
		/* TRACER */
		'temp'		=>	dirname( __DIR__ ). '/config/tracer/temp.php',
		'trace'		=>	dirname( __DIR__ ). '/config/tracer/trace.php',
		
		/* REQUEST */
		'compose' 	=>	dirname( __DIR__ ). '/config/request/compose.php',
        'input_val' =>	dirname( __DIR__ ). '/config/request/inputValidation.php',
        'file_val'  =>	dirname( __DIR__ ). '/config/request/fileValidation.php',
		'req_static'=>	dirname( __DIR__ ). '/config/request/static.php',
		'request' 	=>	dirname( __DIR__ ). '/config/request/request.php',
		
		/* APP */
		'handler' 	=>	dirname( __DIR__ ). '/config/storage/handler.php',
		'session' 	=>	dirname( __DIR__ ). '/config/storage/session.php',
		'init' 		=>	dirname( __DIR__ ). '/config/init.php',
		'global' 	=>	dirname( __DIR__ ). '/config/global.php',
		'blades'	=>	dirname( __DIR__ ). '/config/blades.php'
	];