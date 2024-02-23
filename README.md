# Capa de Abstracción para Almacenamiento en la Nube

Este proyecto tiene como objetivo desarrollar una **capa de abstracción** que facilite el uso de diferentes tecnologías de almacenamiento en la nube. La capa de abstracción se diseñará en dos modalidades:

1. **Librería de Clases y Objetos**: Esta modalidad permitirá integrar la capa de abstracción en aplicaciones desarrolladas con **PHP** y el framework **Symfony**. Proporcionará una interfaz coherente y uniforme para interactuar con diversos servicios de almacenamiento.

2. **API REST**: La segunda modalidad consistirá en una **API REST** que otros proyectos podrán utilizar para acceder a los servicios de almacenamiento. Esto permitirá una integración sencilla en diferentes tecnologías.

## Funcionalidades Clave

- **Uniformidad**: La capa de abstracción ofrecerá los mismos métodos y llamadas para interactuar con servicios de almacenamiento como **Owncloud**, **OneDrive** o **Amazon S3**. Esto simplificará el desarrollo y el mantenimiento de aplicaciones.

- **Gestión de Metadatos**: Además de abstraer el almacenamiento, la capa gestionará un conjunto de **metadatos** asociados a los nodos de almacenamiento. Esto permitirá una mayor flexibilidad y adaptabilidad.

## Implementación

Para desarrollar esta capa de abstracción, podemos aprovechar **librerías de terceros** que ya implementen parte de la funcionalidad. Algunas opciones incluyen:

- **Symfony Components**: Symfony ofrece una colección de **componentes PHP** reutilizables. Estos componentes pueden ser utilizados independientemente del framework Symfony y proporcionan características comunes para el desarrollo de aplicaciones en PHP.

- **Librerías Específicas**: ThePhpLeague ofrece la librería **Flysystem** con metodos para trabajar con cada servicio de almacenamiento (por ejemplo, librerías para Amazon S3, Owncloud, etc.) podremos combinarlas en nuestra capa de abstracción.
