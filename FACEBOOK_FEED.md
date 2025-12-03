# Facebook Product Feed - Esempio

Questo è un esempio del formato XML generato dal modulo per Facebook Shop.

## Formato Feed

Il feed generato segue lo standard RSS 2.0 con namespace Google Shopping (g:):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Nome del Negozio</title>
    <link>https://tuonegozio.com</link>
    <description>Feed prodotti per Facebook Shop</description>
    
    <item>
      <g:id>123</g:id>
      <g:title>Nome Prodotto</g:title>
      <g:description>Descrizione del prodotto</g:description>
      <g:availability>in stock</g:availability>
      <g:condition>new</g:condition>
      <g:price>29.99 EUR</g:price>
      <g:link>https://tuonegozio.com/prodotto</g:link>
      <g:image_link>https://tuonegozio.com/img/p/1/2/3/123.jpg</g:image_link>
      <g:brand>Marca Prodotto</g:brand>
      <g:gtin>1234567890123</g:gtin>
      <g:mpn>SKU123</g:mpn>
      <g:product_type>Categoria</g:product_type>
      <g:google_product_category>Apparel &amp; Accessories &gt; Clothing &gt; Outerwear &gt; Hoodies &amp; Sweatshirts</g:google_product_category>
      <g:identifier_exists>true</g:identifier_exists>
      <g:additional_image_link>https://tuonegozio.com/img/p/1/2/4/124.jpg</g:additional_image_link>
    </item>
    
    <!-- Altri prodotti... -->
    
  </channel>
</rss>
```

## Campi Inclusi

### Campi Obbligatori
- **g:id**: ID univoco del prodotto
- **g:title**: Titolo del prodotto (max 150 caratteri)
- **g:description**: Descrizione del prodotto (max 5000 caratteri)
- **g:availability**: Disponibilità (in stock, out of stock, preorder)
- **g:condition**: Condizione (new)
- **g:price**: Prezzo con valuta (es: 29.99 EUR)
- **g:link**: URL della pagina prodotto
- **g:image_link**: URL immagine principale

### Campi Opzionali (se disponibili)
- **g:brand**: Marca/Brand del prodotto
- **g:gtin**: Codice EAN/UPC
- **g:mpn**: Codice produttore (reference)
- **g:product_type**: Categoria del prodotto
- **g:google_product_category**: Categoria ufficiale Google Shopping mappata automaticamente per OGNI prodotto. Sistema intelligente con analisi del prodotto e fallback garantito (es: "Apparel & Accessories > Clothing > Outerwear > Hoodies & Sweatshirts")
- **g:identifier_exists**: Indica se il prodotto ha GTIN o MPN (true/false)
- **g:additional_image_link**: Immagini aggiuntive (max 10)

## Utilizzo con Facebook

1. Genera il feed dal pannello di configurazione del modulo
2. Copia l'URL del feed mostrato (es: https://tuonegozio.com/facebook_product_feed.xml)
3. Vai su Facebook Commerce Manager
4. Seleziona il tuo catalogo o creane uno nuovo
5. Scegli "Usa Feed Dati" come metodo di caricamento
6. Inserisci l'URL del feed
7. Configura la frequenza di aggiornamento (consigliato: giornaliera)

## Note

- Il feed include solo prodotti **attivi** con tutti i campi obbligatori compilati
- Il file viene generato nella root del negozio (`facebook_product_feed.xml`)
- Si consiglia di rigenerare il feed regolarmente per mantenere i prodotti aggiornati
- Il feed è accessibile pubblicamente via URL
- Le immagini devono essere in formato HTTPS per essere accettate da Facebook
