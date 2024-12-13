import requests
from bs4 import BeautifulSoup
import csv
import time
import re
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.by import By
from selenium.common.exceptions import TimeoutException, WebDriverException
import sys

def extract_text_content(driver):
    """Extrae texto relevante de la página"""
    try:
        # Buscar elementos que suelen contener descripciones
        content_selectors = [
            "//div[contains(@class, 'description')]",
            "//div[contains(@class, 'content')]",
            "//section[contains(@class, 'about')]",
            "//div[contains(@class, 'features')]",
            "//div[contains(@class, 'amenities')]",
            "//div[contains(@class, 'info')]",
            "//article",
            "//main",
            "//div[contains(@class, 'text')]"
        ]
        
        text_content = []
        for selector in content_selectors:
            try:
                elements = driver.find_elements(By.XPATH, selector)
                for element in elements:
                    text = element.text.strip()
                    if text and len(text) > 50:  # Solo textos significativos
                        text_content.append(text)
            except:
                continue
                
        return "\n".join(text_content)
    except:
        return ""

def get_space_details(url):
    try:
        # Configurar Chrome con webdriver-manager
        chrome_options = Options()
        chrome_options.add_argument('--headless')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--log-level=3')  # Suprimir mensajes de DevTools
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.set_page_load_timeout(15)  # Aumentamos el timeout
        
        try:
            driver.get(url)
            time.sleep(3)  # Damos más tiempo para cargar
            texto_completo = driver.page_source.lower()
            contenido_descriptivo = extract_text_content(driver)
        except (TimeoutException, WebDriverException) as e:
            print(f"Error al cargar {url}: {str(e)}", file=sys.stderr)
            driver.quit()
            return None
            
        # Extraer información descriptiva
        descripcion = ""
        if contenido_descriptivo:
            # Limpiar y formatear el texto
            descripcion = re.sub(r'\s+', ' ', contenido_descriptivo)
            descripcion = descripcion[:500] + '...' if len(descripcion) > 500 else descripcion
        
        # Buscar precios
        precios = {
            'precio': None,
            'precio_min': None,
            'precio_max': None
        }
        
        precio_patterns = [
            r'(\d+)[€\s]*/\s*mes',
            r'desde\s*(\d+)[€\s]*',
            r'precio[s]?\s*desde\s*(\d+)[€\s]*',
            r'(\d+)[€\s]*/\s*persona',
            r'tarifa[s]?\s*desde\s*(\d+)[€\s]*',
            r'(\d+)[€\s]*/\s*puesto',
            r'(\d+)[€\s]*/\s*plaza'
        ]
        
        for pattern in precio_patterns:
            matches = re.finditer(pattern, texto_completo)
            for match in matches:
                precio = match.group(1)
                try:
                    precio_num = int(precio)
                    if not precios['precio'] or precio_num < int(precios['precio']):
                        precios['precio_min'] = precios['precio']
                        precios['precio'] = precio
                    elif precio_num > int(precios['precio']):
                        precios['precio_max'] = precio
                except ValueError:
                    continue
        
        # Buscar características y servicios
        caracteristicas = []
        servicios_keywords = [
            'wifi', 'internet', 'fibra óptica', 'sala de reuniones', 'salas de reuniones',
            'café', 'cafetería', 'impresora', 'scanner', 'cocina', 'office equipado',
            'parking', 'terraza', 'aire acondicionado', 'climatización',
            'recepción', 'limpieza', 'seguridad 24h', '24/7', 'acceso 24h',
            'taquillas', 'office', 'eventos', 'formación', 'domiciliación',
            'sala de descanso', 'zona chill out', 'jardín', 'ducha', 
            'teléfono', 'dirección fiscal', 'secretaría', 'paquetería',
            'sala de videollamadas', 'sala de fotografía', 'estudio',
            'espacio exterior', 'zona networking', 'zona relax'
        ]
        
        for keyword in servicios_keywords:
            if keyword in texto_completo:
                caracteristicas.append(keyword)
        
        # Buscar horario
        horario = None
        horario_patterns = [
            r'horario:?\s*([^\n]*)',
            r'abierto:?\s*([^\n]*)',
            r'(\d{1,2}:\d{2}\s*(?:am|pm)?\s*-\s*\d{1,2}:\d{2}\s*(?:am|pm)?)',
            r'24/7',
            r'24 horas'
        ]
        
        for pattern in horario_patterns:
            match = re.search(pattern, texto_completo)
            if match:
                horario = match.group(0).strip()
                break
        
        driver.quit()
        
        # Construir texto de detalles
        detalles = []
        
        if descripcion:
            detalles.append(descripcion)
        
        if precios['precio']:
            precio_texto = f"Desde {precios['precio']}€"
            if precios['precio_max']:
                precio_texto += f" hasta {precios['precio_max']}€"
            detalles.append(precio_texto)
        
        if caracteristicas:
            detalles.append("Servicios y características: " + ", ".join(caracteristicas))
            
        if horario:
            detalles.append("Horario: " + horario)
        
        return " | ".join(detalles) if detalles else None
        
    except Exception as e:
        print(f"Error procesando {url}: {str(e)}", file=sys.stderr)
        if 'driver' in locals():
            driver.quit()
        return None

def update_spaces():
    # Leer el CSV existente
    with open('espacios.txt', 'r', encoding='utf-8') as file:
        reader = csv.DictReader(file)
        espacios = list(reader)
    
    total = len(espacios)
    procesados = 0
    
    # Procesar cada espacio
    for espacio in espacios:
        procesados += 1
        if espacio['URL']:
            print(f"Procesando {espacio['Nombre']}... ({procesados}/{total})", file=sys.stderr)
            detalles_adicionales = get_space_details(espacio['URL'])
            
            if detalles_adicionales:
                # Mantener la valoración original y añadir los nuevos detalles
                valoracion = re.search(r'Valoración: [0-9.]+', espacio['Detalles'])
                ciudad = re.search(r'Espacio coworking en ([^.]+)', espacio['Detalles'])
                
                new_detalles = []
                if ciudad:
                    new_detalles.append(f"Espacio coworking en {ciudad.group(1)}")
                if valoracion:
                    new_detalles.append(valoracion.group(0))
                new_detalles.append(detalles_adicionales)
                
                espacio['Detalles'] = " | ".join(new_detalles)
        
        time.sleep(1)  # Esperar entre requests
        
        # Guardar después de cada espacio procesado
        with open('espacios.txt', 'w', encoding='utf-8', newline='') as file:
            writer = csv.DictWriter(file, fieldnames=espacios[0].keys())
            writer.writeheader()
            writer.writerows(espacios)
    
    print("\n¡Archivo actualizado correctamente!", file=sys.stderr)

if __name__ == "__main__":
    update_spaces()