# Physical Processes and timeline for microgreen crops


***overview***
- Harvest days are wednesdays
- Delivery days are thursdays
- Most crops are 9-12 days to maturity
- Some are longer 14-21 but those are grow to order
- 2 types of customers
	- retail farmers markets, direct to customers
	- wholesale grocery stores, farmers CSA boxes
- Stages of the crops
	- seed soak (only some crops)
	- planting
	- germination (3-8 days crop depending)
	- blackout (1-2 days for certain crops)
	- light (remaining days)
	- harvest
- Crops are grown following precise processes to ensure quality and no problems
- environment is controlled via Home Assistant
	- 71-73F temp
	- 60% relative humidity
	- fans to circulate air following
	- 12/12 light cycles (standard for all crops)
	- Environmental data logged in Home Assistant

Planting
- In order to be efficient with planting we require the following software technologies
	- An ordering system where clients orders can be inputted. This would include one time orders, or recurring orders either weekly, or bi-weekly. 
	- Our planting would be based on these orders as we will be keeping statistics on crops and the harvest weights. 
	- This will allow us to predict harvest weights of future crops and allow us to be very efficient in planting only the number of trays required to meet these orders.
	- As mentioned, we will be keeping robust historical data on all crops that we will use to show statistics based on crops, type, time of year, yields, etc.
	- Our ordering system, product system, consumable inventory, seed price scraping system, invoicing system, payment system, planting system, monitoring system, statistical models all require interelation to operate.
- make up trays with soil
- portion out seed for number of trays being planted
- sew the seed onto the trays
- water it in based on recipe/procedure for that specific crop
- stack trays with standard weight procedure

Germination
- Trays follow specific germination schedules that are unique to each crop cultivar.
- When the germination phase is complete, crops are either moved directly to light. Some crops require a blackout period where they are covered with an upside tray to encourage stretching of the plant.
- To accomodate all the various crops and their unique life cycles, we require each crop to have a specific recipe. A set of instructions and procedures that must be followed.
- Some recipes require liquid fertilizer application on specific days during watering schedule
- Prior to harvest, crops might require a period where they receive no watering. This can be 24 hours prior to harvest, but is unique to each crops recipe.

Blackout
- cover trays with no holed tray flipped upside down
- stretches crop to make it easier to harvest

Light
- Trays are placed on chrome wire shelves under led lights for a 16/8 light cycles
- They are bottom watered daily before the morning light cycles on based on a watering schedule that comes from a crop recipe.
- Trays are rotated each morning to ensure balanced watering in case shelves are slightly out of level
- based on the recipe watering is suspended 24-48 hours before harvesting to allow the crop to dry out which ensures optimal product quality when packaging.

Harvesting
- Our system creates a list of all orders for the week
- Breaks down orders by customer, crops, and mixes.
- One of our products are mixes of crops so we need to be able
- Calculates how much of each crop they need to harvest
	- certain crops are combined to form mixes
		- recipes contain percentage requirements of each crop variety to complete the mixes
	- Makes a list with a detailed breakdown of what type and qty of packaging required 
- Harvest crops and store in large containers in walk in fridge
- As the crops are harvested, harvest weights are captured and logged
	- the harvest list that was created if checked off to ensure adequate harvesting of crops was completed
- Trays are emptied of soil and roots
- Trays are washed and sanitized
- Trays are dried and re-stacked so that they can be used for planting

Packaging
- employee creates a delivery list
	- details qty of each variety and in which packaging
	- labels are put on the packaging and harvest dates written on the labels
- Package the crops based on this list, place in fridge and ready for delivery.

Consumables Inventory Management
- Track inventory levels for:
  - Packaging materials
  - Labels
  - Other consumables
- Monitor reorder thresholds
- Generate reorder alerts when thresholds are reached

Packaging and Delivery
- Generate weekly order aggregation
- Calculate mix requirements based on recipes
- Create harvest checklist
- Create packing checklist
- Track packaging material usage
