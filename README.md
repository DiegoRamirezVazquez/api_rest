# api_rest

Para la practica de API-First primero se definio el contrato de la api la cual nos basamos el openapi y despues se realizo la implementacion con el servidor.
Nos permite solamente consultar, crear y actualizar algunas tareas.

Algunas de las tecnologias que utilizamos con 
- PHP
- Apache
- Mysql
- Docker
- GitHub
- OpenApi 3.0.3
- Swagger UI

Para el desarrollo de esta pratica primero creamos el openapi.yaml que es en donde vamos a definir los endpoints que va a tener nuestra API. Despues implementamos los modelos en la carpeta models, y los recursos, asi como las rutas en el index, esto para cumplir con el contrato que ya habiamos definido desde el incio.

La api nos permite realizar las siguientes operaciones

GET   -> /api/v2/tareas        -> Nos obtiene todas las tareas
POST  -> /api/v2/tareas        -> Crea una nueva tarea
GET   -> /api/v2/tareas/{id}   -> Nos obtiene una tarea en especifico por ID
PUT   -> /api/v2/tareas/{id}   -> Nos actualiza una tarea

Para la base de datos, nos tuvimos de ver la forma de construir la base de datos, que es en donde vamos a almacenar las tareas, y en json quedo una estructura como la siguiente:
                        {
                        "id": 1,
                        "titulo": "Estudiar API-First",
                        "completada": false,
                        "fecha_creacion": "2026-09-07 23:05:57"
                        }

Los campos que declaramos en la base de datos son:
id               ->   Integer     ->  Es el identificador unico de la tarea
titulo           ->   String      ->  Titulo o descripcion de la tarea
completada       ->   Boolean     ->  Indica si la tarea esta completada
fecha_creacion   ->   DateTime    ->  Fecha y hora de creacion

Para documentar con Swagger podemos realizar las mismas pruebas, solamente utilizando la interfaz grafica que se nos brinda para que sea interactiva.



Para poder levantar el proyecto solamente debemos de hacer una serie de pasos:
- Si estamos de manera local, solamente vamos a
    1. Iniciar Apache
    2. Ingresar la URL de donde esta nuestro proyecto en este caso es http://localhost:8080/api/v2/tareas
    3. Si queremos iniciar desde swagger vamos a ingresar en un navegador con http://localhost:8080/api-docs/
    4. Y ya podemos probar los endpoints

- Si estamos en el servidor que se nos asigno, solo basta con ingresar el URL destinado en postman o en el navegador, ya en cualquier de los dos los configuramos para que funcione de manera correcta.