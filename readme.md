Inštalácia
-----------

- plugin rozbalíme do priečinka wp-content/plugins
- aktivuje sa v sekcii Pluginy

![](images/plugin.png)

- v menu sa vytvori nova položka FHB Kika API

![](images/menu.png)

Záložka nastavenie
------------------

![](images/setting.png)

- API AppId a Secret podla zoe
- Sandbox mod - slúži na testovanie. Ak je zapnutý, posiela plugin požiadavky na dev server.
- Odosielať po vytvorení - voľba či sa ma objednávka odosielať hneď po vytvorení
- Default prepravca - vyber štandardného prepravcu. Zoznam sa aktualizuje raz za 7 dni.
- Prefix API ID - pridá prefix k API ID. V prípade ak ma užívateľ viac obchodov aby neprišlo ku kolízii čísiel. 
- Mapovanie statusov - tu sa dajú namapovať Kika API notify linky na Woocomerce statusy. Napr: 
   - Notifikácia confirmed na Spracováva sa
   - Notifikácia sent na Vybavená
   - Notifikácia returned na Zrušenia
- Platobne metódy - nastavenie pri ktorej metóde sa posiela suma do API. Defaultne je zapnutá Dobierka

Záložka produkty
----------------

![](images/product.png)

- Záložka slúži na prehlad a hromadný export produktov do systému.
- Produkt sa dá alternatívne exportovať v detaile produktu.
- Každý jednoduchý produkt musí mat pred exportom nastavene unikátne SKU. Nastavuje sa v Detail produktu/Údaje o produkte/Sklad/Katalógové číslo

![](images/simple.png)

- Pri variabilných produktov musí mat nastavene SKU každá varianta. Nastavuje sa v Detail produktu/Údaje o produkte/Varianty/Katalógové číslo

![](images/variable.png)

Záložka objednávky
------------------

- Záložka slúži na prehlad a hromadný export objednávok do systému.
- Exportujú sa neexportované objednávky v stave Prijatá a Spracováva sa
- Objednávka sa dá alternatívne exportovať v detaile objednávky, kde sa dajú upraviť tiež parametre exportu ak COD a dopravca.

![](images/order.png)