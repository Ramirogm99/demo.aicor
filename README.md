# Pasos para la instalación del backend
- Crear archivo .env del .env.example
- Generar base de datos con el nombre asociado en el .env
- Realizar los *php artisan migrate*
- Usar *Composer Install* en la terminar
- *npm install*
- Instalar laragon para realizar las peticiones bien ya que tiene generacion de virtual hosts y la url es propia de laragon
- En laragon cambiar en opciones el "Hostname template" de {name}.test a {name}.com. Esto es para que a la hora de iniciar sesión por Google 
    Esta no de fallos debido a un uri missmatch y sobretodo por las url de postman que de {baseurl} es la que tienen
## Requerimientos
- PHP 8.4.1
