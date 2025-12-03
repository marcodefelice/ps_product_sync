# Mappatura Avanzata Categorie Google Shopping

Il sistema ora utilizza un algoritmo avanzato per mappare le categorie PrestaShop alle categorie ufficiali di Google Shopping, analizzando il prodotto per categorie ultra-specifiche.

## Come funziona l'algoritmo avanzato

1. **Rilevamento categoria base**: Usa la categoria PrestaShop più specifica
2. **Analisi del prodotto**: Analizza nome, descrizione e brand del prodotto
3. **Mappatura intelligente**: Applica regole specifiche per categoria finale
4. **Raffinamento**: Aggiunge specificità basata sul contenuto del prodotto

## Esempi di mappatura intelligente

### Abbigliamento Ultra-Specifico
| Input | Categoria Google Shopping |
|-------|---------------------------|
| Categoria: "Felpe" + Titolo: "Felpa Running Nike" | `Apparel & Accessories > Clothing > Activewear` |
| Categoria: "Scarpe" + Titolo: "Scarpe Running Adidas" | `Apparel & Accessories > Shoes > Athletic Shoes > Running Shoes` |
| Categoria: "Magliette" + Descrizione: "T-shirt cotone uomo" | `Apparel & Accessories > Clothing > Shirts & Tops > T-shirts` |
| Categoria: "Jeans" + Titolo: "Jeans Slim Fit" | `Apparel & Accessories > Clothing > Pants > Jeans` |

### Pattern di Riconoscimento Avanzati
- **Sport specifici**: "Running shoes" → `Athletic Shoes > Running Shoes`
- **Materiali**: "Jeans denim" → `Pants > Jeans`
- **Occasioni d'uso**: "Scarpe eleganti" → `Dress Shoes`
- **Genere**: "Maglietta uomo" → (mantiene categoria ma può influenzare il targeting)

## Categorie supportate (estese)

### Abbigliamento Dettagliato
- **Felpe**: `Outerwear > Hoodies & Sweatshirts`
- **Maglioni**: `Shirts & Tops > Sweaters`
- **T-shirt**: `Shirts & Tops > T-shirts`
- **Canotte**: `Shirts & Tops > Tank Tops`
- **Polo**: `Shirts & Tops > Polos`
- **Camicie**: `Shirts & Tops > Dress Shirts`
- **Jeans**: `Pants > Jeans`
- **Shorts**: `Pants > Shorts`
- **Leggings**: `Pants > Leggings`
- **Blazer**: `Outerwear > Blazers`
- **Vestiti**: `Dresses`
- **Gonne**: `Skirts`

### Scarpe Ultra-Specifiche
- **Running**: `Shoes > Athletic Shoes > Running Shoes`
- **Basket**: `Shoes > Athletic Shoes > Basketball Shoes`
- **Calcio**: `Shoes > Athletic Shoes > Soccer Shoes`
- **Tennis**: `Shoes > Athletic Shoes > Tennis Shoes`
- **Sneakers**: `Shoes > Athletic Shoes`
- **Eleganti**: `Shoes > Dress Shoes`
- **Mocassini**: `Shoes > Loafers`
- **Ballerine**: `Shoes > Flats`
- **Tacchi**: `Shoes > High Heels`
- **Stivali**: `Shoes > Boots`
- **Sandali**: `Shoes > Sandals`

### Accessori Specifici
- **Zaini**: `Handbags, Wallets & Cases > Backpacks`
- **Portafogli**: `Handbags, Wallets & Cases > Wallets & Money Clips`
- **Berretti**: `Clothing Accessories > Hats > Knit Caps`
- **Sciarpe**: `Clothing Accessories > Scarves & Shawls`
- **Guanti**: `Clothing Accessories > Gloves & Mittens`
- **Calze**: `Clothing Accessories > Socks`

### Gioielli Dettagliati
- **Anelli**: `Jewelry > Rings`
- **Collane**: `Jewelry > Necklaces`
- **Bracciali**: `Jewelry > Bracelets`
- **Orecchini**: `Jewelry > Earrings`
- **Orologi**: `Jewelry > Watches`

## Regole di Analisi Automatica

### Pattern di Riconoscimento
```
Pattern Sportivo: "running|corsa" + "scarpe" → Running Shoes
Pattern Materiale: "denim|jeans" → Jeans
Pattern Elegante: "elegante|formal" + "scarpe" → Dress Shoes
Pattern Stagionale: "estivo|summer" → (influenza sottocategoria)
```

### Analisi del Contenuto
Il sistema analizza:
- **Titolo del prodotto**: Parole chiave principali
- **Descrizione**: Dettagli tecnici e d'uso
- **Brand**: Indicazioni sul tipo di prodotto
- **Materiali**: Tessuti e composizione
- **Occasioni d'uso**: Sport, elegante, casual

## Test del Sistema Avanzato

Usa il file di test aggiornato:
```bash
php test_feed_categories.php
```

Il test mostra:
1. **Mappature base**: Come vengono mappate le categorie standard
2. **Analisi prodotti**: Come vengono analizzati i prodotti reali
3. **Categorie finali**: Le categorie Google Shopping specifiche generate

## Personalizzazione Avanzata

Nel file `category_mappings.json` puoi aggiungere:

```json
{
    "category_mappings": {
        "mappings": {
            "tua_categoria_specifica": "Apparel & Accessories > Clothing > Pants > Dress Pants",
            "prodotti_premium": "Apparel & Accessories > Clothing > Suits > Tuxedos"
        }
    }
}
```

## Vantaggi del Sistema Avanzato

1. **Ultra-specifico**: Categorie molto dettagliate per miglior posizionamento
2. **Intelligente**: Analizza il prodotto, non solo la categoria
3. **Adattivo**: Si adatta ai contenuti dei prodotti
4. **Scalabile**: Facile aggiungere nuove regole
5. **Accurato**: Riduce le categorizzazioni errate

## Note Tecniche

- Le regole sono applicate in ordine di priorità
- Le mappature personalizzate hanno precedenza su quelle automatiche
- Il sistema è case-insensitive e gestisce caratteri speciali
- Fallback intelligente se non trova corrispondenze specifiche