# Analytics Dashboard Documentation

## Overview

The Analytics Dashboard provides comprehensive insights into your booking system's performance, including revenue tracking, booking statistics, service popularity, staff performance, and customer behavior patterns.

## Accessing the Dashboard

**Admin Only Feature**

Navigate to: **Admin → Analytics** in the main menu

Or visit: `/admin/analytics`

## Features

### 1. **Time Period Filters**

Choose from predefined periods or set a custom date range:

- **Today** - Current day statistics
- **Week** - Current week (Monday to Sunday)
- **Month** - Current month (default view)
- **Quarter** - Current quarter
- **Year** - Current year
- **Custom** - Select specific start and end dates

### 2. **Key Metrics Cards**

#### Total Revenue
- Shows total paid revenue for selected period
- Displays growth percentage compared to previous period
- Color-coded indicator (green for positive, red for negative)

#### Total Bookings
- Total number of bookings created in the period
- Shows count of confirmed bookings
- Quick overview of booking volume

#### Conversion Rate
- Percentage of bookings that were confirmed
- Number of cancelled bookings
- Helps track booking quality

#### Average Booking Value
- Average revenue per confirmed booking
- Based on total confirmed bookings
- Useful for pricing strategy

### 3. **Revenue Analytics**

#### Daily Revenue Chart (Line Chart)
- Day-by-day revenue breakdown
- Shows revenue trends over time
- Helps identify peak revenue days
- Displays in currency format (MYR)

### 4. **Service Analytics**

#### Top Services (Doughnut Chart)
- Visual breakdown of most popular services
- Based on booking count
- Shows top 5 services
- Color-coded segments

### 5. **Booking Patterns**

#### Popular Time Slots (Bar Chart)
- Shows which hours of the day are most popular
- Displayed in 24-hour format (e.g., 09:00, 14:00)
- Helps optimize staff scheduling
- Based on confirmed bookings only

#### Bookings by Day of Week (Bar Chart)
- Distribution of bookings across weekdays
- Identifies busiest days
- Useful for staffing decisions
- Shows Sunday through Saturday

### 6. **Staff Performance Table**

Comprehensive breakdown per staff member:
- Staff name
- Total bookings handled
- Total revenue generated
- Sorted by booking count (highest first)

**Use Cases:**
- Identify top performers
- Balance workload
- Performance reviews
- Bonus/commission calculations

### 7. **Cancellation Analysis Table**

Service-by-service cancellation tracking:
- Service name
- Total bookings
- Cancelled bookings count
- Cancellation rate percentage
- High rates (>20%) highlighted in red

**Use Cases:**
- Identify problematic services
- Customer satisfaction issues
- Pricing problems
- Service quality concerns

## Data Sources

### Revenue Data
- Source: `payments` table
- Filter: `status = 'paid'`
- Based on `paid_at` date

### Booking Statistics
- Source: `bookings` table
- Includes all statuses:
  - Confirmed
  - Cancelled
  - Pending Payment
  - Expired
  - Refunded

### Service Popularity
- Based on confirmed bookings
- Joined with `service_prices` and `services`
- Counts unique bookings per service

### Staff Metrics
- Based on confirmed bookings
- Joined with `staff` table
- Aggregates by staff member

### Time Slot Analysis
- Extracted from booking `starts_at` field
- Hour of day (0-23)
- Confirmed bookings only

## Technical Details

### Backend Services

**AnalyticsService** (`app/Services/AnalyticsService.php`)
- `getRevenueStats()` - Revenue totals and growth
- `getDailyRevenue()` - Day-by-day breakdown
- `getBookingStats()` - Booking counts by status
- `getPopularServices()` - Top services ranking
- `getStaffPerformance()` - Staff metrics
- `getPopularTimeSlots()` - Hour distribution
- `getBookingsByDayOfWeek()` - Day distribution
- `getAverageBookingValue()` - Average calculation
- `getCancellationRateByService()` - Cancellation analysis
- `getDashboardData()` - Comprehensive data retrieval

### Frontend Components

**Vue Components:**
- `Chart.vue` - Reusable chart wrapper (Chart.js)
- `Admin/Analytics/Index.vue` - Main dashboard page

**Charts Library:**
- Chart.js v4.x
- vue-chartjs v5.x
- Responsive and interactive charts

### Performance Considerations

1. **Query Optimization**
   - All queries use proper indexes
   - Aggregations done at database level
   - Minimal data transfer to frontend

2. **Date Range Limits**
   - No hard limits set
   - Recommended: Max 1 year for performance
   - Large ranges may slow down page load

3. **Caching (Future Enhancement)**
   - Consider caching dashboard data
   - Cache invalidation on new bookings/payments
   - Suggested TTL: 15-30 minutes

## Sample Data

To populate the dashboard with sample data for testing:

```bash
php artisan db:seed --class=AnalyticsSampleDataSeeder
```

This creates 30 days of sample bookings with realistic patterns:
- More bookings on weekdays
- Random time distributions
- 80% confirmed, 20% other statuses
- Associated payments for confirmed bookings

## Future Enhancements

### Planned Features
1. **Export Functionality**
   - CSV export of all data
   - PDF reports
   - Scheduled email reports

2. **Advanced Filters**
   - Filter by specific service
   - Filter by staff member
   - Filter by customer

3. **Additional Metrics**
   - Customer retention rate
   - New vs returning customers
   - Average time to booking
   - Peak booking hours heatmap

4. **Forecasting**
   - Revenue predictions
   - Booking trends
   - Demand forecasting

5. **Comparison Views**
   - Year-over-year comparison
   - Month-over-month trends
   - Service performance comparison

6. **Real-time Updates**
   - WebSocket integration
   - Live dashboard updates
   - Notifications for milestones

7. **Custom Dashboards**
   - User-configurable widgets
   - Drag-and-drop layout
   - Saved dashboard views

## Troubleshooting

### No Data Showing

**Check:**
1. Date range includes actual bookings
2. User has admin role
3. Database has bookings and payments
4. Run sample data seeder for testing

### Slow Loading

**Solutions:**
1. Reduce date range
2. Check database indexes
3. Review query performance
4. Consider implementing caching

### Chart Not Rendering

**Check:**
1. Browser console for errors
2. Chart.js loaded correctly
3. Data format is correct
4. Try different browser

## API Endpoints

### GET /admin/analytics

**Parameters:**
- `period` (optional): today|week|month|quarter|year|custom
- `start_date` (optional): YYYY-MM-DD (required if period=custom)
- `end_date` (optional): YYYY-MM-DD (required if period=custom)

**Response:**
Returns comprehensive analytics object with all metrics and charts data.

## Security

- Protected by admin role middleware
- Only users with 'admin' role can access
- Date range validation prevents SQL injection
- No sensitive customer data exposed in reports

## Best Practices

1. **Regular Review**
   - Check analytics weekly
   - Track month-over-month growth
   - Identify trends early

2. **Data-Driven Decisions**
   - Use cancellation rates to improve services
   - Adjust pricing based on demand
   - Optimize staff schedules using time slot data

3. **Performance Monitoring**
   - Watch for declining conversion rates
   - Monitor revenue trends
   - Track staff performance regularly

4. **Sharing Insights**
   - Export reports for team meetings
   - Share metrics with stakeholders
   - Use data for strategic planning
