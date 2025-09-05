export interface Column {
  name: string;
  type: string;
  nullable?: boolean;
  primary?: boolean;
  unique?: boolean;
}

export interface Table {
  id: string;
  name: string;
  columns: Column[];
  position?: { x: number; y: number };
}

export interface Relationship {
  id: string;
  source: string;
  target: string;
  type: 'hasOne' | 'hasMany' | 'belongsTo' | 'belongsToMany' | 'morphTo';
  sourceKey: string;
  foreignKey: string;
}

export interface ErdData {
  tables: Table[];
  relationships: Relationship[];
}

export interface ErdConfig {
  enabled: boolean;
  route: {
    path: string;
    middleware: string[];
    name: string;
  };
  environments: string[];
  cache: {
    enabled: boolean;
    ttl: number;
    key: string;
  };
}