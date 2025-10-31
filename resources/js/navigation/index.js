import {
  LayoutGrid,
  Circle,
  User,
  LifeBuoy,
  Archive,
  ClipboardList,
  Users,
  ChartSpline,
  Tag,
  Settings,
  WalletCards,
  Truck,
  MapPin,
  ShieldUser,
  CreditCard,
  Store,
  // Added Archive icon
} from "lucide-vue-next";
// https://lucide.dev/icons/

export default {
  navMain: [
    {
      action: "read",
      title: "Dashboard",
      url: "/dashboard",
      icon: LayoutGrid,
    },
    {
      action: "admin",
      title: "舊版後台",
      url: "/admin",
      icon: Archive, // Changed to Archive to indicate legacy
    },
    {
      action: "orders.interface",
      title: "訂單管理",
      url: "#",
      icon: ClipboardList,
      items: [
        {
          action: "orders",
          title: "所有訂單",
          url: "/orders",
        },
        {
          action: "transactions",
          title: "收款明細",
          url: "/admin/transactions",
        },
        {
          action: "fulfillments",
          title: "出貨明細",
          url: "/admin/fulfillments",
        },
      ],
    },
    {
      action: "products.interface",
      title: "商品管理",
      url: "#",
      icon: Tag,
      items: [
        {
          action: "products",
          title: "所有商品",
          url: "/products",
        },
        {
          action: "categories",
          title: "商品分類",
          url: "/admin/categories",
        },
        {
          action: "inventories",
          title: "庫存管理",
          url: "/inventories",
        },
      ],
    },
    {
      action: "customers.interface",
      title: "客戶管理",
      url: "#",
      icon: Users,
      items: [
        {
          action: "customers",
          title: "所有客戶",
          url: "/customers",
        },
      ],
    },
    {
      action: "stores.interface",
      title: "門市管理",
      url: "#",
      icon: Store,
      items: [
        {
          action: "locations",
          title: "進貨單",
          url: "/purchase-orders",
        },
        {
          action: "locations",
          title: "轉移單",
          url: "/transfer-orders",
        },
        {
          action: "locations",
          title: "盤點單",
          url: "/adjustment-orders",
        },
      ],
    },
  ],
  navSecondary: [
    {
      action: "installments",
      title: "分期試算",
      url: "/installments",
      icon: CreditCard,
    },
    {
      action: "analytics.interface",
      title: "分析報表",
      url: "/analytics",
      icon: ChartSpline,
      items: [
        {
          action: "analytics",
          title: "銷售報告",
          url: "/analytics",
        },
        {
          action: "analytics",
          title: "庫存分析",
          url: "/analytics/inventory",
        },
      ],
    },
    {
      action: "settings.interface",
      title: "系統設定",
      url: "/settings",
      icon: Settings,
      items: [
        {
          action: "templates",
          title: "模板設定",
          url: "/admin/form-templates",
          icon: Settings,
        },
        {
          title: "收款設定",
          action: "gateways",
          url: "/admin/gateways",
          icon: WalletCards,
        },
        {
          action: "logistics",
          title: "物流設定",
          url: "/admin/logistics",
          icon: Truck,
        },
        {
          action: "locations",
          title: "地址設定",
          url: "/locations",
          icon: MapPin,
        },
      ],
    },
    {
      action: "users",
      title: "員工設定",
      url: "/admin/users",
      icon: User,
    },
    {
      action: "roles",
      title: "角色設定",
      url: "/admin/roles",
      icon: ShieldUser,
    },
  ],
};
